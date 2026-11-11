<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClientActivity;
use App\Models\Document;
use App\Services\ClientTracking;
use App\Services\DossierWorkflow;
use App\Services\PlanAccess;
use App\Services\PlanPreview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function download(Request $request, Document $document)
    {
        $this->authorizeDocument($request, $document);
        if (PlanAccess::isProtected($document)) {
            return redirect()->route('portal.documents.view', $document);
        }
        abort_unless(Storage::disk('local')->exists($document->path), 404);
        ClientTracking::record(ClientActivity::DOWNLOADED, '« '.$document->displayName().' » téléchargé', ['document' => $document], $request);

        return Storage::disk('local')->response($document->path, $document->original_name ?: basename($document->path));
    }

    /** Consultation en ligne des plans : pages en images avec filigrane, sans téléchargement. */
    public function view(Request $request, Document $document)
    {
        $this->authorizeDocument($request, $document);
        if (! PlanAccess::isProtected($document)) {
            return redirect()->route('portal.documents.download', $document);
        }
        ClientTracking::record(ClientActivity::VIEWED, '« '.$document->displayName().' » consulté en ligne', ['document' => $document], $request);

        try {
            $pages = PlanPreview::pageCount($document);
        } catch (\Throwable $e) {
            report($e);
            $pages = 0;
        }

        return response()->view('portal.plan', ['document' => $document, 'dossier' => $document->dossier, 'pages' => $pages])
            ->header('Cache-Control', 'private, no-store');
    }

    public function page(Request $request, Document $document, int $page)
    {
        $this->authorizeDocument($request, $document);
        abort_unless(PlanAccess::isProtected($document), 404);
        $path = PlanPreview::pagePath($document, $page);
        abort_unless($path, 404);

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="apercu-'.$page.'.png"',
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex',
        ]);
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
