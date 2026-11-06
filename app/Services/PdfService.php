<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    public static function quote(Quote $quote): \Barryvdh\DomPDF\PDF
    {
        $quote->loadMissing('lines', 'client', 'dossier');
        $company = $quote->company_snapshot ?: CompanySetting::current()->snapshot();
        $client = $quote->client_snapshot ?: $quote->client->snapshot();

        return Pdf::loadView('pdf.quote', compact('quote', 'company', 'client'))->setPaper('a4');
    }

    public static function invoice(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->loadMissing('lines', 'client', 'dossier', 'parent', 'payments', 'quote');
        $company = $invoice->company_snapshot ?: CompanySetting::current()->snapshot();
        $client = $invoice->client_snapshot ?: $invoice->client->snapshot();

        return Pdf::loadView('pdf.invoice', compact('invoice', 'company', 'client'))->setPaper('a4');
    }

    public static function quoteFilename(Quote $quote): string
    {
        return 'Devis-'.($quote->number ?: 'brouillon-'.$quote->id).'.pdf';
    }

    public static function invoiceFilename(Invoice $invoice): string
    {
        $label = $invoice->isCreditNote() ? 'Avoir' : ($invoice->type->value === 'acompte' ? 'Facture-acompte' : 'Facture');

        return $label.'-'.($invoice->number ?: 'brouillon-'.$invoice->id).'.pdf';
    }

    /** Stocke la version définitive (figée) du PDF. */
    public static function storeInvoice(Invoice $invoice): string
    {
        $path = 'factures/'.now()->format('Y').'/'.self::invoiceFilename($invoice);
        Storage::disk('local')->put($path, self::invoice($invoice)->output());
        $invoice->forceFill(['pdf_path' => $path])->saveQuietly();

        return $path;
    }

    public static function storeSignedQuote(Quote $quote): string
    {
        $path = 'devis-signes/'.now()->format('Y').'/'.self::quoteFilename($quote);
        Storage::disk('local')->put($path, self::quote($quote)->output());
        $quote->forceFill(['signed_pdf_path' => $path])->saveQuietly();

        return $path;
    }
}
