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

        return self::numberPages(Pdf::loadView('pdf.quote', compact('quote', 'company', 'client'))->setPaper('a4'), 'Devis '.($quote->number ?: '(brouillon)'));
    }

    public static function invoice(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->loadMissing('lines', 'client', 'dossier', 'parent', 'payments', 'quote');
        $company = $invoice->company_snapshot ?: CompanySetting::current()->snapshot();
        $client = $invoice->client_snapshot ?: $invoice->client->snapshot();

        return self::numberPages(Pdf::loadView('pdf.invoice', compact('invoice', 'company', 'client'))->setPaper('a4'), $invoice->type->label().' '.($invoice->number ?: '(brouillon)'));
    }

    /** Ajoute « Devis D202611-132 • 1/2 » en bas à droite de chaque page. */
    private static function numberPages(\Barryvdh\DomPDF\PDF $pdf, string $label): \Barryvdh\DomPDF\PDF
    {
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'bold');
        $text = $label.' • {PAGE_NUM}/{PAGE_COUNT}';
        $width = $dompdf->getFontMetrics()->getTextWidth(str_replace(['{PAGE_NUM}', '{PAGE_COUNT}'], '9', $text), $font, 7.5);
        $canvas->page_text($canvas->get_width() - 34 - $width, $canvas->get_height() - 30, $text, $font, 7.5, [0.12, 0.14, 0.16]);

        return $pdf;
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
