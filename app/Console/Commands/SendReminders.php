<?php

namespace App\Console\Commands;

use App\Enums\DossierStatus;
use App\Enums\InvoiceStatus;
use App\Enums\QuoteStatus;
use App\Models\CompanySetting;
use App\Models\Dossier;
use App\Models\Invoice;
use App\Models\Quote;
use App\Notifications\AdminAlert;
use App\Notifications\QuoteReminder;
use App\Services\InvoiceService;
use App\Support\Admin;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'nyleo:reminders {--dry-run : Affiche sans envoyer}';

    protected $description = 'Relance les factures impayées et les devis non signés, alerte sur les délais d\'instruction';

    public function handle(): int
    {
        $s = CompanySetting::current();
        $dry = (bool) $this->option('dry-run');

        // Devis expirés
        Quote::where('status', QuoteStatus::Envoye->value)->whereDate('valid_until', '<', now())->get()
            ->each(fn (Quote $q) => $dry ?: $q->forceFill(['status' => QuoteStatus::Expire])->save());

        if ($s->auto_reminders) {
            // Factures impayées : 1re relance X jours après l'échéance, puis tous les Y jours, au maximum N fois
            $invoices = Invoice::with('client')
                ->whereIn('status', [InvoiceStatus::Emise->value, InvoiceStatus::PartiellementPayee->value])
                ->where('type', '!=', 'avoir')
                ->where('reminder_count', '<', $s->reminder_max)
                ->whereDate('due_date', '<=', now()->subDays($s->reminder_first_days))
                ->get()
                ->filter(fn (Invoice $i) => $i->client?->email
                    && (! $i->last_reminder_at || $i->last_reminder_at->lte(now()->subDays($s->reminder_interval_days))));

            foreach ($invoices as $i) {
                $this->line("Relance facture {$i->number} → {$i->client->email}");
                $dry ?: InvoiceService::remind($i);
            }

            // Devis envoyés non signés : une seule relance
            $quotes = Quote::with('client')->where('status', QuoteStatus::Envoye->value)->where('reminder_count', 0)
                ->where('sent_at', '<=', now()->subDays($s->quote_reminder_days))->get()
                ->filter(fn (Quote $q) => $q->client?->email);
            foreach ($quotes as $q) {
                $this->line("Relance devis {$q->number} → {$q->client->email}");
                if (! $dry) {
                    $q->client->notify(new QuoteReminder($q));
                    $q->forceFill(['reminder_count' => 1, 'last_reminder_at' => now()])->saveQuietly();
                    $q->dossier?->log('relance', "Relance envoyée pour le devis {$q->number}");
                }
            }
        }

        // Alertes pour toi : décision tacite atteinte, pièces mairie bientôt hors délai
        $due = Dossier::where('status', DossierStatus::EnInstruction->value)->whereDate('decision_due_date', today())->get();
        foreach ($due as $d) {
            $dry ?: Admin::notify(new AdminAlert("Délai d'instruction atteint — {$d->reference}",
                "Sans décision reçue, l'autorisation de {$d->client->display_name} est en principe tacite depuis aujourd'hui. Pense à demander le certificat de non-opposition.",
                route('filament.admin.resources.dossiers.view', $d)));
        }
        $incomplete = Dossier::where('status', DossierStatus::Incomplet->value)->whereDate('incomplete_deadline', today()->addDays(15))->get();
        foreach ($incomplete as $d) {
            $dry ?: Admin::notify(new AdminAlert("Pièces mairie à fournir sous 15 jours — {$d->reference}",
                "Le dossier de {$d->client->display_name} sera rejeté si les pièces ne sont pas déposées avant le {$d->incomplete_deadline->format('d/m/Y')}.",
                route('filament.admin.resources.dossiers.view', $d)));
        }

        $this->info('Terminé.');

        return self::SUCCESS;
    }
}
