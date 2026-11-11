<?php

namespace App\Http\Controllers\Portal;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\ClientActivity;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Quote;
use App\Services\ClientTracking;
use App\Services\PdfService;
use App\Services\PlanAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** « Télécharger le PDF » depuis un e-mail : lien signé, sans connexion, valable quelques mois. */
class FileController extends Controller
{
    public function download(Request $request, string $kind, int $id)
    {
        return match ($kind) {
            'devis' => $this->quote(Quote::findOrFail($id), $request),
            'facture' => $this->invoice(Invoice::findOrFail($id), $request),
            'document' => $this->document(Document::findOrFail($id), $request),
            default => abort(404),
        };
    }

    private function quote(Quote $quote, Request $request)
    {
        abort_if($quote->status === QuoteStatus::Brouillon, 404);
        $name = PdfService::quoteFilename($quote);
        ClientTracking::record(ClientActivity::DOWNLOADED, "PDF « {$name} » téléchargé", ['quote' => $quote], $request);

        return $quote->signed_pdf_path && Storage::disk('local')->exists($quote->signed_pdf_path)
            ? Storage::disk('local')->response($quote->signed_pdf_path, $name)
            : PdfService::quote($quote)->stream($name);
    }

    private function invoice(Invoice $invoice, Request $request)
    {
        abort_unless((bool) $invoice->number, 404);
        $name = PdfService::invoiceFilename($invoice);
        ClientTracking::record(ClientActivity::DOWNLOADED, "PDF « {$name} » téléchargé", ['invoice' => $invoice], $request);

        return $invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)
            ? Storage::disk('local')->response($invoice->pdf_path, $name)
            : PdfService::invoice($invoice)->stream($name);
    }

    private function document(Document $document, Request $request)
    {
        // Plans pas encore téléchargeables : consultation en ligne dans l'espace client.
        if (PlanAccess::isProtected($document)) {
            return redirect()->away(PlanAccess::viewerUrl($document));
        }
        abort_unless(Storage::disk('local')->exists($document->path), 404);
        ClientTracking::record(ClientActivity::DOWNLOADED, '« '.$document->displayName().' » téléchargé', ['document' => $document], $request);

        return Storage::disk('local')->response($document->path, $document->original_name ?: basename($document->path));
    }
}
