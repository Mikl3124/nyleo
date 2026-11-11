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
        // Date et lieu de naissance (CERFA) demandés avant d'ouvrir les plans.
        if (self::needsBirth($request)) {
            return response()->view('portal.plan', ['document' => $document, 'dossier' => $document->dossier, 'pages' => 0, 'needsBirth' => true, 'client' => $request->user('client')])
                ->header('Cache-Control', 'private, no-store');
        }
        ClientTracking::record(ClientActivity::VIEWED, '« '.$document->displayName().' » consulté en ligne', ['document' => $document], $request);

        try {
            $pages = PlanPreview::pageCount($document);
        } catch (\Throwable $e) {
            report($e);
            $pages = 0;
        }

        return response()->view('portal.plan', ['document' => $document, 'dossier' => $document->dossier, 'pages' => $pages, 'needsBirth' => false])
            ->header('Cache-Control', 'private, no-store');
    }

    public function page(Request $request, Document $document, int $page)
    {
        $this->authorizeDocument($request, $document);
        abort_unless(PlanAccess::isProtected($document), 404);
        abort_if(self::needsBirth($request), 403);
        $path = PlanPreview::pagePath($document, $page);
        abort_unless($path, 404);

        return response()->file($path, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'inline; filename="apercu-'.$page.'.jpg"',
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /** Formulaire « date et lieu de naissance » affiché avant les plans. */
    public function saveBirth(Request $request, Document $document)
    {
        $this->authorizeDocument($request, $document);
        $data = $request->validate([
            'birth_date' => ['required', 'date', 'before:today'],
            'birth_place' => ['required', 'string', 'max:120'],
        ], [], ['birth_date' => 'date de naissance', 'birth_place' => 'lieu de naissance']);
        $client = $request->user('client');
        $client->fill($data)->save();
        $document->dossier->log('infos_client', 'Le client a indiqué sa date et son lieu de naissance', true);

        return redirect()->route('portal.documents.view', $document);
    }

    private static function needsBirth(Request $request): bool
    {
        $client = $request->user('client');

        return ! $client->birth_date || ! filled($client->birth_place);
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
