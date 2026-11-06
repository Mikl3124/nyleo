<?php

namespace App\Console\Commands;

use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\Numbering;
use Illuminate\Console\Command;

/**
 * Fin de la phase de test : supprime toutes les factures et avoirs (avec paiements et PDF)
 * puis repositionne les compteurs. À ne lancer qu'avant la première vraie facture.
 */
class ResetInvoices extends Command
{
    protected $signature = 'nyleo:reset-invoices
        {--invoice= : Prochain n° de facture (par défaut 1)}
        {--credit-note= : Prochain n° d\'avoir (par défaut 1)}
        {--force : Ne pas demander de confirmation}';

    protected $description = 'Supprime toutes les factures et avoirs de test et remet la numérotation à zéro';

    public function handle(): int
    {
        $count = Invoice::withTrashed()->count();
        if (! $this->option('force') && ! $this->confirm("Supprimer définitivement {$count} facture(s) et avoir(s), avec leurs paiements ?")) {
            return self::FAILURE;
        }

        // Les avoirs d'abord : ils sont rattachés à leur facture d'origine.
        foreach (Invoice::withTrashed()->orderByRaw("type = 'avoir' desc")->orderByDesc('id')->pluck('id') as $id) {
            if ($invoice = Invoice::withTrashed()->find($id)) {
                InvoiceService::delete($invoice);
            }
        }

        $invoiceNext = (int) ($this->option('invoice') ?: Numbering::nextFromExisting('invoice'));
        $creditNext = (int) ($this->option('credit-note') ?: Numbering::nextFromExisting('credit_note'));
        CompanySetting::current()->forceFill([
            'invoice_next_number' => max(1, $invoiceNext),
            'credit_note_next_number' => max(1, $creditNext),
        ])->save();

        $this->info("{$count} pièce(s) supprimée(s). Prochaine facture : n° {$invoiceNext}, prochain avoir : n° {$creditNext}.");

        return self::SUCCESS;
    }
}
