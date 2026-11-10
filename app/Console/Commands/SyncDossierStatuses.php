<?php

namespace App\Console\Commands;

use App\Enums\InvoiceType;
use App\Models\Dossier;
use App\Models\Invoice;
use App\Services\DossierWorkflow;
use App\Services\InvoiceService;
use Illuminate\Console\Command;

/**
 * Remet d'aplomb les dossiers existants : factures sans dossier rattachées au dossier en cours
 * du client, puis statut des dossiers en phase commerciale recalculé d'après leurs devis et factures.
 */
class SyncDossierStatuses extends Command
{
    protected $signature = 'nyleo:sync-dossiers {--dry-run : Affiche les corrections sans rien modifier}';

    protected $description = 'Met le statut des dossiers en accord avec leurs devis et factures';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $orphans = Invoice::whereNull('dossier_id')->where('type', '!=', InvoiceType::Avoir->value)->with('client')->get();
        foreach ($orphans as $invoice) {
            $target = $invoice->client?->dossierForNewInvoice();
            $this->line(sprintf('Facture %s (%s) : %s', $invoice->number ?? 'brouillon #'.$invoice->id, $invoice->client?->display_name,
                $target ? "rattachée au dossier {$target->reference}" : 'nouveau dossier créé'));
            if (! $dry) {
                InvoiceService::ensureDossier($invoice);
                // Les avoirs suivent leur facture.
                Invoice::where('parent_invoice_id', $invoice->id)->whereNull('dossier_id')->update(['dossier_id' => $invoice->dossier_id]);
            }
        }

        $changed = 0;
        foreach (Dossier::whereIn('status', array_map(fn ($s) => $s->value, DossierWorkflow::COMMERCIAL_STATUSES))->with('client')->get() as $dossier) {
            $status = DossierWorkflow::statusFromDocuments($dossier);
            if ($status === $dossier->status) {
                continue;
            }
            $changed++;
            $this->line(sprintf('%s (%s) : %s → %s', $dossier->reference, $dossier->client?->display_name, $dossier->status->label(), $status->label()));
            if (! $dry) {
                DossierWorkflow::syncFromDocuments($dossier);
            }
        }

        $this->info(($dry ? 'Simulation : ' : '').count($orphans).' facture(s) sans dossier, '.$changed.' statut(s) de dossier '.($dry ? 'à corriger' : 'corrigé(s)').'.');

        return self::SUCCESS;
    }
}
