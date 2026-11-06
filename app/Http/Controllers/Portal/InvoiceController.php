<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function pdf(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->client_id === $request->user('client')->id && $invoice->number, 404);
        if ($invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)) {
            return Storage::disk('local')->response($invoice->pdf_path, PdfService::invoiceFilename($invoice));
        }

        return PdfService::invoice($invoice)->stream(PdfService::invoiceFilename($invoice));
    }
}
