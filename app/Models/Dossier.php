<?php

namespace App\Models;

use App\Enums\DossierStatus;
use App\Enums\ExternalPaymentStatus;
use App\Enums\Priority;
use App\Enums\Procedure;
use App\Enums\WaitingOn;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dossier extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => DossierStatus::class,
            'procedure' => Procedure::class,
            'abf' => 'boolean',
            'site_same_as_client' => 'boolean',
            'is_individual_house' => 'boolean',
            'deposit_date' => 'date',
            'incomplete_notified_at' => 'date',
            'incomplete_deadline' => 'date',
            'decision_due_date' => 'date',
            'decision_date' => 'date',
            'received_at' => 'date',
            'follow_up_at' => 'date',
            'instruction_started_at' => 'date',
            'incomplete_answered_at' => 'date',
            'waiting_on' => WaitingOn::class,
            'priority' => Priority::class,
            'external_payment_status' => ExternalPaymentStatus::class,
            'commission_amount' => 'decimal:2',
            'agreed_price' => 'decimal:2',
            'commission_paid' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // « Identique à l'adresse du client » : le terrain reprend toujours l'adresse de la fiche client.
        static::saving(function (Dossier $dossier) {
            if ($dossier->site_same_as_client && $client = Client::find($dossier->client_id)) {
                $dossier->site_address = $client->address;
                $dossier->site_postal_code = $client->postal_code;
                $dossier->site_city = $client->city;
                if ($client->postal_code && ($dossier->isDirty('site_postal_code') || ! $dossier->department)) {
                    $dossier->department = substr($client->postal_code, 0, 2);
                }
            }
        });

        static::creating(function (Dossier $dossier) {
            if (! $dossier->reference) {
                $year = now()->format('Y');
                $count = static::withTrashed()->where('reference', 'like', "N{$year}-%")->count() + 1;
                $dossier->reference = sprintf('N%s-%03d', $year, $count);
            }
            if (! $dossier->department && $dossier->site_postal_code) {
                $dossier->department = substr($dossier->site_postal_code, 0, 2);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(DossierEvent::class)->latest();
    }

    /** Demandes de pièces de la mairie, la plus récente en premier. */
    public function pieceRequests(): HasMany
    {
        return $this->hasMany(PieceRequest::class)->orderByDesc('requested_at')->orderByDesc('id');
    }

    public function openPieceRequests(): HasMany
    {
        return $this->hasMany(PieceRequest::class)->whereNull('sent_at');
    }

    /** Dossiers où quelqu'un doit agir : le champ « En attente de » ou une demande de pièces non envoyée. */
    public function scopeWaitingOn($query, WaitingOn $who)
    {
        return $query->where(fn ($q) => $q->where('waiting_on', $who->value)
            ->orWhereHas('openPieceRequests', fn ($r) => $r->where('waiting_on', $who->value)));
    }

    public function log(string $type, string $message, bool $visibleToClient = false, array $meta = []): DossierEvent
    {
        return $this->events()->create([
            'type' => $type,
            'message' => $message,
            'visible_to_client' => $visibleToClient,
            'meta' => $meta ?: null,
        ]);
    }

    /** Prescripteurs habituels (proposés dans la saisie, on peut en taper un autre). */
    public const PRESCRIBERS = ['Direct', 'AKENA — Thibault', 'AKENA — Rémi', 'AKENA — Cédric', 'Permis-Construire.com'];

    public static function closedStatuses(): array
    {
        return [DossierStatus::Accorde->value, DossierStatus::Refuse->value, DossierStatus::Abandonne->value];
    }

    /** Relance prévue aujourd'hui ou déjà passée. */
    public function isFollowUpDue(): bool
    {
        return $this->follow_up_at !== null && $this->follow_up_at->lte(today());
    }

    /** Dossiers dont la date de relance est arrivée. */
    public function scopeFollowUpDue($query)
    {
        return $query->whereNotNull('follow_up_at')->whereDate('follow_up_at', '<=', today());
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', static::closedStatuses());
    }

    public function siteAddress(): string
    {
        return trim(collect([$this->site_address, trim($this->site_postal_code.' '.$this->site_city)])->filter()->implode(', '));
    }

    /** Délai d'instruction de droit commun (Code de l'urbanisme, R*423-23 et R*423-24). */
    public function instructionMonths(): int
    {
        $base = match ($this->procedure) {
            Procedure::DP => 1,
            Procedure::PC, Procedure::PCArchi => $this->is_individual_house ? 2 : 3,
            Procedure::PA => 3,
            default => 2,
        };

        return $base + ($this->abf ? 1 : 0);
    }

    public function computeDecisionDueDate(): ?CarbonInterface
    {
        return $this->deposit_date?->copy()->addMonthsNoOverflow($this->instructionMonths());
    }

    /**
     * Ce que Mickaël doit faire maintenant sur ce dossier (null = on attend le client ou la mairie, ou dossier clos).
     * Source unique pour le tableau de bord, la liste et la fiche dossier.
     */
    public function nextAction(): ?string
    {
        // L'action notée à la main (suivi) prime sur celle déduite du statut.
        if (filled($this->follow_up_action)) {
            return $this->follow_up_action;
        }
        if ($open = $this->openPieceRequests()->orderBy('deadline')->first()) {
            return $open->waiting_on === WaitingOn::Client
                ? null
                : 'Fournir les pièces à la mairie'.($open->deadline ? ' avant le '.$open->deadline->format('d/m/Y') : '');
        }

        return match ($this->status) {
            DossierStatus::Nouveau => 'Rappeler le client et faire le devis',
            DossierStatus::DevisSigne, DossierStatus::PiecesAttendues => 'Démarrer les plans',
            DossierStatus::PlansEnCours => 'Finir les plans et les envoyer au client',
            DossierStatus::PlansValides => 'Déposer en mairie (vérifier le paiement)',
            DossierStatus::Incomplet => 'Fournir les pièces à la mairie'.($this->incomplete_deadline ? ' avant le '.$this->incomplete_deadline->format('d/m/Y') : ''),
            DossierStatus::EnInstruction => $this->decision_due_date && $this->decision_due_date->lte(now()->addDays(7))
                ? 'Décision attendue le '.$this->decision_due_date->format('d/m/Y').' : appeler la mairie'
                : null,
            default => null,
        };
    }

    /** Qui a la main : utile pour savoir pourquoi un dossier n'avance pas. */
    public function waitingFor(): ?string
    {
        if ($this->openPieceRequests()->where('waiting_on', WaitingOn::Client->value)->exists()) {
            return 'Le client doit fournir les pièces demandées par la mairie';
        }

        return match ($this->status) {
            DossierStatus::DevisEnvoye => 'Le client doit signer le devis',
            DossierStatus::PlansAValider => 'Le client doit valider les plans',
            DossierStatus::Depose, DossierStatus::EnInstruction => $this->decision_due_date
                ? 'La mairie doit répondre avant le '.$this->decision_due_date->format('d/m/Y')
                : 'La mairie instruit le dossier',
            default => null,
        };
    }

    /** Ce que le client doit faire sur ce dossier (liste « Mes dossiers »), ou null s'il n'a rien à faire. */
    public function clientTodo(): ?string
    {
        return match (true) {
            $this->quotes()->where('status', 'envoye')->get()->contains(fn (Quote $q) => $q->isSignable()) => 'Devis à signer',
            $this->documents()->where('requires_validation', true)->whereNull('validated_at')->whereNull('change_requested_at')->exists() => 'Plans à vérifier',
            $this->invoices()->whereNotNull('number')->where('type', '!=', 'avoir')->whereIn('status', ['emise', 'partiellement_payee'])->exists() => 'Facture à régler',
            $this->openPieceRequests()->where('waiting_on', WaitingOn::Client->value)->exists() => 'Documents demandés par la mairie',
            default => null,
        };
    }

    /** Phrase affichée au client en tête de son dossier : où on en est et ce qui va se passer, sans jargon. */
    public function clientStatusMessage(): string
    {
        return match ($this->status) {
            DossierStatus::Nouveau => 'J\'ai bien reçu votre demande. Je vous appelle pour en parler, puis je vous envoie le devis.',
            DossierStatus::DevisEnvoye => 'Votre devis est prêt. Dès qu\'il est signé, je commence votre dossier.',
            DossierStatus::DevisSigne, DossierStatus::PiecesAttendues, DossierStatus::PlansEnCours => 'Je dessine vos plans. Vous recevrez un e-mail dès qu\'ils sont prêts à vérifier. Rien à faire de votre côté.',
            DossierStatus::PlansAValider => 'Vos plans sont prêts : il ne reste qu\'à les vérifier.',
            DossierStatus::PlansValides => $this->invoices()->whereIn('status', ['emise', 'partiellement_payee'])->exists()
                ? 'Plans validés, merci ! Je dépose votre dossier en mairie dès réception du règlement.'
                : 'Plans validés, merci ! Je dépose votre dossier en mairie dans les prochains jours.',
            DossierStatus::Depose, DossierStatus::EnInstruction => 'Votre dossier est entre les mains de la mairie. Rien à faire de votre côté'
                .($this->decision_due_date ? ' : la réponse est attendue au plus tard le '.$this->decision_due_date->format('d/m/Y').'.' : '.'),
            DossierStatus::Incomplet => $this->openPieceRequests()->where('waiting_on', WaitingOn::Client->value)->exists()
                ? 'La mairie a demandé des compléments : j\'ai besoin de quelques documents de votre part.'
                : 'La mairie a demandé des compléments. Je m\'en occupe et je vous contacte si j\'ai besoin de vous.',
            DossierStatus::Accorde => 'Bonne nouvelle : votre projet est accordé ! Pensez à afficher l\'autorisation sur le terrain, visible depuis la rue, pendant toute la durée des travaux.',
            DossierStatus::Refuse => 'La mairie a refusé le projet. Je vous appelle pour voir ensemble les solutions possibles.',
            DossierStatus::Abandonne => 'Ce dossier est clos.',
        };
    }

    public function signedQuote(): ?Quote
    {
        return $this->quotes()->where('status', 'signe')->latest('signed_at')->first();
    }

    public function emailMessages(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ClientActivity::class);
    }
}
