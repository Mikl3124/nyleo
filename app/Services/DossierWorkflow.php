<?php

namespace App\Services;

use App\Enums\DocumentCategory;
use App\Enums\DossierStatus;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\QuoteStatus;
use App\Models\Document;
use App\Models\Dossier;
use App\Models\PieceRequest;
use App\Notifications\AdminAlert;
use App\Notifications\DossierUpdate;
use App\Support\Admin;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DossierWorkflow
{
    /** Statuts « commerciaux » : ils se déduisent entièrement des devis et des factures du dossier. */
    public const COMMERCIAL_STATUSES = [
        DossierStatus::Nouveau, DossierStatus::DevisEnvoye, DossierStatus::DevisSigne, DossierStatus::PiecesAttendues,
    ];

    /**
     * Statut que les devis et factures imposent au dossier : un devis signé, une facture
     * ou un devis envoyé font sortir le dossier de « Nouvelle demande ».
     */
    public static function statusFromDocuments(Dossier $dossier): DossierStatus
    {
        if ($dossier->quotes()->where('status', QuoteStatus::Signe->value)->exists()) {
            return in_array($dossier->status, [DossierStatus::DevisSigne, DossierStatus::PiecesAttendues], true)
                ? $dossier->status
                : DossierStatus::PiecesAttendues;
        }
        // Une facture (même en brouillon) sans devis signé : le travail est commandé, on en est aux plans.
        if (static::billedInvoices($dossier)->exists()) {
            return DossierStatus::PlansEnCours;
        }
        if ($dossier->quotes()->where('status', QuoteStatus::Envoye->value)->exists()) {
            return DossierStatus::DevisEnvoye;
        }

        return DossierStatus::Nouveau;
    }

    /** Factures et acomptes du dossier, brouillons compris, sauf avoirs et factures annulées. */
    public static function billedInvoices(Dossier $dossier)
    {
        return $dossier->invoices()
            ->where('type', '!=', InvoiceType::Avoir->value)
            ->where('status', '!=', InvoiceStatus::Annulee->value);
    }

    /**
     * Remet le statut d'accord avec les devis et factures, tant que le dossier est en phase commerciale.
     * Appelé à chaque enregistrement ou suppression d'un devis ou d'une facture. Une fois les plans
     * commencés, le statut suit le travail (plans, mairie) et n'est plus touché ici.
     */
    public static function syncFromDocuments(?Dossier $dossier): void
    {
        if (! $dossier || ! $dossier->exists || $dossier->trashed()) {
            return;
        }
        $dossier->status = Dossier::whereKey($dossier->id)->value('status') ?? $dossier->status;
        $dossier->syncOriginalAttribute('status');
        if (! in_array($dossier->status, static::COMMERCIAL_STATUSES, true)) {
            return;
        }

        $status = static::statusFromDocuments($dossier);
        if ($status !== $dossier->status) {
            $dossier->forceFill(['status' => $status])->saveQuietly();
            $dossier->log('statut', 'Statut mis à jour d\'après les devis et factures : '.$status->label());
        }
    }

    /** Le dessinateur met des plans en ligne et demande la validation du client. */
    public static function submitPlans(Document $plan, ?string $message = null): void
    {
        $plan->forceFill(['requires_validation' => true, 'visible_to_client' => true])->save();
        $dossier = $plan->dossier;
        $dossier->update(['status' => DossierStatus::PlansAValider]);
        $dossier->log('plans_envoyes', "« {$plan->displayName()} » envoyés pour validation", true);
        $dossier->client->notify(new DossierUpdate(
            $dossier,
            'Vos plans sont prêts',
            [
                "Les plans de votre projet sont prêts : {$plan->displayName()}.",
                $message,
                'Merci de les consulter dans votre espace, puis de cliquer sur « Je valide les plans » ou « Je demande une modification ».',
            ],
            'Consulter mes plans',
            $plan,
        ));
    }

    /** Validation des plans par le client : la facture est générée et envoyée automatiquement. */
    public static function clientValidatesPlans(Document $plan): void
    {
        DB::transaction(function () use ($plan) {
            $plan->forceFill(['validated_at' => now()])->save();
            $dossier = $plan->dossier;
            $dossier->update(['status' => DossierStatus::PlansValides]);
            $dossier->log('plans_valides', "« {$plan->displayName()} » validés par le client", true);

            if ($quote = $dossier->signedQuote()) {
                $invoice = InvoiceService::createFinalFromQuote($quote);
                if ($invoice->isDraft()) {
                    InvoiceService::issue($invoice, notify: true);
                }
            }
        });

        Admin::notify(new AdminAlert(
            "Plans validés — {$plan->dossier->reference}",
            "{$plan->dossier->client->display_name} a validé les plans {$plan->displayName()}. La facture a été envoyée automatiquement.",
            route('filament.admin.resources.dossiers.view', $plan->dossier),
        ));
    }

    public static function clientRequestsChanges(Document $plan, string $feedback): void
    {
        $plan->forceFill(['change_requested_at' => now(), 'client_feedback' => $feedback])->save();
        $dossier = $plan->dossier;
        $dossier->update(['status' => DossierStatus::PlansEnCours]);
        $dossier->log('modification_demandee', "Modification demandée sur {$plan->displayName()} : {$feedback}", true);
        Admin::notify(new AdminAlert(
            "Modification demandée — {$dossier->reference}",
            "{$dossier->client->display_name} : « {$feedback} »",
            route('filament.admin.resources.dossiers.view', $dossier),
        ));
    }

    /** Dépôt en mairie : calcule la date de décision tacite et prévient le client. */
    public static function markDeposited(Dossier $dossier, Carbon $date, ?string $number, ?string $channel, ?string $receiptPath = null): void
    {
        $dossier->fill([
            'deposit_date' => $date,
            'deposit_number' => $number,
            'deposit_channel' => $channel,
            'status' => DossierStatus::EnInstruction,
        ]);
        $dossier->decision_due_date = $dossier->computeDecisionDueDate();
        $dossier->save();

        if ($receiptPath) {
            $dossier->documents()->create([
                'category' => DocumentCategory::Recepisse,
                'name' => 'Récépissé de dépôt'.($number ? " n° {$number}" : ''),
                'path' => $receiptPath,
                'visible_to_client' => true,
            ]);
        }

        $dossier->log('depose', "Dossier déposé en mairie le {$date->format('d/m/Y')}".($number ? " (n° {$number})" : ''), true);
        $dossier->client->notify(new DossierUpdate(
            $dossier,
            'Votre dossier a été déposé en mairie',
            [
                "Votre dossier a été déposé le {$date->format('d/m/Y')}".($number ? ", sous le numéro {$number}" : '').'.',
                "La mairie dispose en principe de {$dossier->instructionMonths()} mois pour l'instruire, soit jusqu'au {$dossier->decision_due_date->format('d/m/Y')}, sauf demande de pièces complémentaires.",
                'Le récépissé est disponible dans votre espace.',
            ],
            'Voir mon dossier',
        ));
    }

    /**
     * La mairie demande des pièces : on enregistre la demande (il peut y en avoir plusieurs),
     * le délai d'instruction ne court plus, en principe 3 mois pour répondre.
     */
    public static function markIncomplete(Dossier $dossier, Carbon $notifiedAt, ?string $details, ?Carbon $deadline = null,
        string $waitingOn = 'moi', ?string $letterPath = null, bool $notify = true): PieceRequest
    {
        $deadline ??= $notifiedAt->copy()->addMonthsNoOverflow(3);
        $request = $dossier->pieceRequests()->create([
            'requested_at' => $notifiedAt,
            'pieces' => $details,
            'deadline' => $deadline,
            'waiting_on' => $waitingOn,
        ]);
        if ($letterPath) {
            $dossier->documents()->create(['category' => DocumentCategory::CourrierMairie, 'name' => 'Demande de pièces du '.$notifiedAt->format('d/m/Y'), 'path' => $letterPath]);
        }
        $dossier->update([
            'status' => DossierStatus::Incomplet,
            'incomplete_notified_at' => $notifiedAt,
            'incomplete_deadline' => $deadline,
        ]);
        $dossier->log('incomplet', 'La mairie demande des pièces complémentaires'.($details ? " : {$details}" : ''), true);

        if ($notify) {
            $dossier->client->notify(new DossierUpdate(
                $dossier,
                'La mairie demande des pièces complémentaires',
                array_values(array_filter([
                    'La mairie a demandé des compléments pour votre dossier.',
                    $details,
                    $waitingOn === 'client'
                        ? "J'ai besoin de votre aide pour certains documents : merci de me les envoyer depuis votre espace, avant le {$deadline->copy()->subDays(15)->format('d/m/Y')} si possible."
                        : "Je m'en occupe et je reviens vers vous si j'ai besoin d'informations de votre part.",
                ])),
                'Voir mon dossier',
            ));
        }

        return $request;
    }

    /**
     * Pièces complémentaires envoyées à la mairie : la demande est close, et quand plus aucune
     * n'est ouverte le délai d'instruction repart à zéro à partir de cette date.
     */
    public static function markCompleted(Dossier $dossier, Carbon $date, ?PieceRequest $request = null): void
    {
        $open = $request ? collect([$request]) : $dossier->openPieceRequests()->get();
        foreach ($open as $r) {
            $r->update(['sent_at' => $date]);
        }

        $dossier->incomplete_answered_at = $date;
        $next = $dossier->openPieceRequests()->orderBy('deadline')->first();
        if ($next) {
            $dossier->incomplete_notified_at = $next->requested_at;
            $dossier->incomplete_deadline = $next->deadline;
        } else {
            // La date de dépôt reste celle d'origine ; le délai d'instruction repart de l'envoi des pièces.
            $dossier->status = DossierStatus::EnInstruction;
            $dossier->decision_due_date = $date->copy()->addMonthsNoOverflow($dossier->instructionMonths());
        }
        $dossier->save();
        $dossier->log('complete', "Pièces complémentaires envoyées à la mairie le {$date->format('d/m/Y')}", true);
    }

    public static function markDecision(Dossier $dossier, bool $granted, Carbon $date, ?string $arretePath = null): void
    {
        $dossier->update([
            'status' => $granted ? DossierStatus::Accorde : DossierStatus::Refuse,
            'decision_date' => $date,
        ]);
        if ($arretePath) {
            $dossier->documents()->create([
                'category' => DocumentCategory::Arrete,
                'name' => 'Arrêté du '.$date->format('d/m/Y'),
                'path' => $arretePath,
                'visible_to_client' => true,
            ]);
        }
        $dossier->log('decision', ($granted ? 'Autorisation accordée' : 'Autorisation refusée').' le '.$date->format('d/m/Y'), true);
        $dossier->client->notify(new DossierUpdate(
            $dossier,
            $granted ? 'Bonne nouvelle : votre projet est accordé' : 'Décision de la mairie',
            $granted
                ? ["La mairie a accordé votre {$dossier->procedure->label()} le {$date->format('d/m/Y')}.", "Pensez à afficher l'autorisation sur le terrain, visible depuis la voie publique, pendant toute la durée des travaux."]
                : ["La mairie a rendu une décision défavorable le {$date->format('d/m/Y')}.", 'Je vous appelle pour voir ensemble les solutions possibles.'],
            'Voir mon dossier',
        ));
    }

    /**
     * Mise à jour du suivi (ce qui remplaçait une ligne du fichier Excel) : prochaine action,
     * qui doit agir, date de relance, priorité, pièces manquantes, et une note datée au journal.
     */
    public static function updateFollowUp(Dossier $dossier, array $data): void
    {
        $fields = ['follow_up_action', 'waiting_on', 'waiting_on_details', 'follow_up_at', 'priority', 'missing_pieces'];
        $dossier->fill(array_intersect_key($data, array_flip($fields)));
        $changed = $dossier->isDirty();
        $dossier->save();

        $note = trim((string) ($data['note'] ?? ''));
        if ($note !== '') {
            $dossier->log('suivi', $note, (bool) ($data['note_visible'] ?? false));
        } elseif ($changed) {
            $parts = array_filter([
                $dossier->follow_up_action ? 'À faire : '.$dossier->follow_up_action : null,
                $dossier->waiting_on ? 'En attente de : '.mb_strtolower($dossier->waiting_on->label()) : null,
                $dossier->follow_up_at ? 'relance le '.$dossier->follow_up_at->format('d/m/Y') : null,
            ]);
            $dossier->log('suivi', 'Suivi mis à jour'.($parts ? ' — '.implode(' ; ', $parts) : ''));
        }
    }

    /**
     * Deux dossiers pour le même projet : tout passe dans $keep (devis, factures, pièces, journal,
     * suivi des e-mails), ses champs vides sont complétés par $duplicate, puis $duplicate est supprimé.
     */
    public static function merge(Dossier $keep, Dossier $duplicate): void
    {
        if ($keep->is($duplicate) || $keep->client_id !== $duplicate->client_id) {
            throw new \InvalidArgumentException('Seuls deux dossiers distincts du même client peuvent être fusionnés.');
        }

        DB::transaction(function () use ($keep, $duplicate) {
            foreach (['quotes', 'invoices', 'documents', 'dossier_events', 'piece_requests', 'email_messages', 'client_activities'] as $table) {
                DB::table($table)->where('dossier_id', $duplicate->id)->update(['dossier_id' => $keep->id]);
            }

            $skip = ['id', 'reference', 'client_id', 'status', 'created_at', 'updated_at', 'deleted_at'];
            foreach ($duplicate->getAttributes() as $field => $value) {
                if (! in_array($field, $skip, true) && filled($value) && blank($keep->getAttributes()[$field] ?? null)) {
                    $keep->setAttribute($field, $duplicate->getAttribute($field));
                }
            }
            $keep->save();

            $keep->log('fusion', "Dossier {$duplicate->reference} « {$duplicate->title} » fusionné dans celui-ci");
            $duplicate->delete();
        });
    }
}
