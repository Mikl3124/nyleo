<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Quote;
use App\Services\PdfService;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function quote(Quote $quote)
    {
        if ($quote->signed_pdf_path && Storage::disk('local')->exists($quote->signed_pdf_path)) {
            return Storage::disk('local')->response($quote->signed_pdf_path, PdfService::quoteFilename($quote));
        }

        return PdfService::quote($quote)->stream(PdfService::quoteFilename($quote));
    }

    public function invoice(Invoice $invoice)
    {
        if ($invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)) {
            return Storage::disk('local')->response($invoice->pdf_path, PdfService::invoiceFilename($invoice));
        }

        return PdfService::invoice($invoice)->stream(PdfService::invoiceFilename($invoice));
    }

    public function document(Document $document)
    {
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->response($document->path, $document->original_name ?: basename($document->path));
    }
}
