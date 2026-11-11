<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClientActivity;
use App\Models\Invoice;
use App\Services\ClientTracking;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    /** La facture en ligne : détail, reste à régler et moyens de paiement (carte, virement). */
    public function show(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->client_id === $request->user('client')->id && $invoice->number, 404);
        if (! ClientTracking::isStaff($request)) {
            ClientTracking::record(ClientActivity::VIEWED, $invoice->type->label().' '.$invoice->number.' consultée en ligne', ['invoice' => $invoice], $request);
        }

        return view('portal.invoice', ['invoice' => $invoice->load('lines', 'dossier', 'payments'), 'client' => $request->user('client')]);
    }

    public function pdf(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->client_id === $request->user('client')->id && $invoice->number, 404);
        ClientTracking::record(ClientActivity::DOWNLOADED, 'PDF « '.PdfService::invoiceFilename($invoice).' » téléchargé', ['invoice' => $invoice], $request);
        if ($invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)) {
            return Storage::disk('local')->response($invoice->pdf_path, PdfService::invoiceFilename($invoice));
        }

        return PdfService::invoice($invoice)->stream(PdfService::invoiceFilename($invoice));
    }
}
