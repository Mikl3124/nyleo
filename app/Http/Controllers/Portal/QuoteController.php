<?php

namespace App\Http\Controllers\Portal;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Services\PdfService;
use App\Services\QuoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuoteController extends Controller
{
    public function show(Request $request, Quote $quote)
    {
        $this->authorizeQuote($request, $quote);
        if (! $quote->viewed_at) {
            $quote->forceFill(['viewed_at' => now()])->saveQuietly();
            $quote->dossier?->log('devis_consulte', "Devis {$quote->number} consulté par le client");
        }

        return view('portal.quote', ['quote' => $quote->load('lines', 'dossier'), 'client' => $request->user('client')]);
    }

    public function pdf(Request $request, Quote $quote)
    {
        $this->authorizeQuote($request, $quote);
        if ($quote->signed_pdf_path && Storage::disk('local')->exists($quote->signed_pdf_path)) {
            return Storage::disk('local')->response($quote->signed_pdf_path, PdfService::quoteFilename($quote));
        }

        return PdfService::quote($quote)->stream(PdfService::quoteFilename($quote));
    }

    public function sign(Request $request, Quote $quote)
    {
        $this->authorizeQuote($request, $quote);
        $data = $request->validate([
            'signer_name' => ['required', 'string', 'min:3', 'max:120'],
            'accept' => ['accepted'],
        ], ['accept.accepted' => 'Merci de cocher la case « Bon pour accord ».'], ['signer_name' => 'nom et prénom']);

        if (! $quote->isSignable()) {
            return back()->withErrors(['signer_name' => 'Ce devis ne peut plus être signé. Contactez-moi pour en recevoir un nouveau.']);
        }

        QuoteService::sign($quote, $data['signer_name'], $request->ip(), (string) $request->userAgent());

        return redirect()->route('portal.dossiers.show', $quote->dossier_id ?? 0)
            ->with('status', 'Merci, votre devis est signé ! Je vous recontacte très vite pour la suite.');
    }

    private function authorizeQuote(Request $request, Quote $quote): void
    {
        abort_unless($quote->client_id === $request->user('client')->id && $quote->status !== QuoteStatus::Brouillon, 404);
    }
}
