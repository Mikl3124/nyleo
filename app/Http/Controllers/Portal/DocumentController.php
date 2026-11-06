<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\DossierWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function download(Request $request, Document $document)
    {
        $this->authorizeDocument($request, $document);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->response($document->path, $document->original_name ?: basename($document->path));
    }

    public function validatePlan(Request $request, Document $document)
    {
        $this->authorizeDocument($request, $document);
        abort_unless($document->isAwaitingValidation(), 403);
        DossierWorkflow::clientValidatesPlans($document);

        return back()->with('status', 'Merci ! Les plans sont validés. Vous allez recevoir la facture par e-mail ; je déposerai le dossier en mairie dès réception du règlement.');
    }

    public function requestChange(Request $request, Document $document)
    {
        $this->authorizeDocument($request, $document);
        abort_unless($document->isAwaitingValidation(), 403);
        $data = $request->validate(['feedback' => ['required', 'string', 'max:3000']], [], ['feedback' => 'modifications souhaitées']);
        DossierWorkflow::clientRequestsChanges($document, $data['feedback']);

        return back()->with('status', 'Merci, votre demande de modification a bien été transmise. Je reviens vers vous avec une nouvelle version.');
    }

    private function authorizeDocument(Request $request, Document $document): void
    {
        abort_unless($document->visible_to_client && $document->dossier->client_id === $request->user('client')->id, 404);
    }
}
