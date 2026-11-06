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
            'birth_date' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'birth_place' => ['required', 'string', 'min:2', 'max:120'],
            'accept' => ['accepted'],
            'early_start' => ['nullable', 'boolean'],
        ], [
            'accept.accepted' => 'Merci de cocher la case « Bon pour accord ».',
            'birth_date.required' => 'Merci d\'indiquer votre date de naissance.',
            'birth_date.before' => 'La date de naissance doit être dans le passé.',
            'birth_place.required' => 'Merci d\'indiquer votre lieu de naissance.',
        ], ['signer_name' => 'nom et prénom', 'birth_date' => 'date de naissance', 'birth_place' => 'lieu de naissance']);

        if (! $quote->isSignable()) {
            return back()->withErrors(['signer_name' => 'Ce devis ne peut plus être signé. Contactez-moi pour en recevoir un nouveau.']);
        }

        // Gardées sur la fiche client pour le CERFA : on ne les redemande plus ensuite.
        $request->user('client')->forceFill([
            'birth_date' => $data['birth_date'],
            'birth_place' => trim($data['birth_place']),
        ])->save();

        QuoteService::sign($quote, $data['signer_name'], $request->ip(), (string) $request->userAgent(), $request->boolean('early_start'));

        return redirect()->route('portal.dossiers.show', $quote->dossier_id ?? 0)
            ->with('status', 'Merci, votre devis est signé ! Je vous recontacte très vite pour la suite.');
    }

    private function authorizeQuote(Request $request, Quote $quote): void
    {
        abort_unless($quote->client_id === $request->user('client')->id && $quote->status !== QuoteStatus::Brouillon, 404);
    }
}
