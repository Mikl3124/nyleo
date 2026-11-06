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

    public function signedQuote(): ?Quote
    {
        return $this->quotes()->where('status', 'signe')->latest('signed_at')->first();
    }
}
