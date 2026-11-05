<?php

namespace App\Http\Controllers\Portal;

use App\Enums\DocumentCategory;
use App\Enums\DossierStatus;
use App\Http\Controllers\Controller;
use App\Models\Dossier;
use App\Notifications\AdminAlert;
use App\Support\Admin;
use Illuminate\Http\Request;

class DossierController extends Controller
{
    public function index(Request $request)
    {
        $client = $request->user('client');
        $dossiers = $client->dossiers()->latest()->get();

        if ($dossiers->count() === 1) {
            return redirect()->route('portal.dossiers.show', $dossiers->first());
        }

        return view('portal.index', compact('client', 'dossiers'));
    }

    public function show(Request $request, Dossier $dossier)
    {
        $this->authorizeDossier($request, $dossier);
        $dossier->load(['quotes' => fn ($q) => $q->whereNotIn('status', ['brouillon'])->latest(),
            'invoices' => fn ($q) => $q->whereNotNull('number')->latest(),
            'documents' => fn ($q) => $q->where('visible_to_client', true)->latest(),
            'events' => fn ($q) => $q->where('visible_to_client', true)->latest()->limit(15)]);

        return view('portal.dossier', [
            'client' => $request->user('client'),
            'dossier' => $dossier,
            'steps' => DossierStatus::clientSteps(),
            'current' => $dossier->status->clientStep(),
        ]);
    }

    public function upload(Request $request, Dossier $dossier)
    {
        $this->authorizeDossier($request, $dossier);
        $request->validate([
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['file', 'max:20480', 'mimes:jpg,jpeg,png,heic,heif,webp,pdf'],
        ], [], ['files' => 'fichiers', 'files.*' => 'fichier']);

        foreach ($request->file('files') as $file) {
            $path = $file->store('dossiers/'.$dossier->id, 'local');
            $isImage = str_starts_with((string) $file->getMimeType(), 'image/');
            $dossier->documents()->create([
                'category' => $isImage ? DocumentCategory::Photo : DocumentCategory::Autre,
                'name' => $file->getClientOriginalName(),
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => 'client',
            ]);
        }
        $n = count($request->file('files'));
        $dossier->log('upload_client', "Le client a envoyé {$n} fichier(s)", true);
        Admin::notify(new AdminAlert("Nouveaux fichiers — {$dossier->reference}", "{$dossier->client->display_name} a envoyé {$n} fichier(s).",
            route('filament.admin.resources.dossiers.view', $dossier), mail: false));

        return back()->with('status', 'Merci, vos fichiers ont bien été envoyés.');
    }

    public function updateInfo(Request $request, Dossier $dossier)
    {
        $this->authorizeDossier($request, $dossier);
        $data = $request->validate([
            'birth_date' => ['nullable', 'date', 'before:today'],
            'birth_place' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'site_address' => ['nullable', 'string', 'max:190'],
            'site_postal_code' => ['nullable', 'string', 'max:10'],
            'site_city' => ['nullable', 'string', 'max:120'],
            'cadastre_section' => ['nullable', 'string', 'max:10'],
            'cadastre_number' => ['nullable', 'string', 'max:20'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $client = $request->user('client');
        $client->fill(array_filter(collect($data)->only(['birth_date', 'birth_place', 'phone'])->all(), 'filled'))->save();
        $dossier->fill(array_filter(collect($data)->only(['site_address', 'site_postal_code', 'site_city', 'cadastre_section', 'cadastre_number'])->all(), 'filled'))->save();

        $dossier->log('infos_client', 'Le client a complété ses informations'.(filled($data['message'] ?? null) ? ' : « '.$data['message'].' »' : ''), true);
        Admin::notify(new AdminAlert("Informations reçues — {$dossier->reference}", "{$client->display_name} a complété ses informations."
            .(filled($data['message'] ?? null) ? ' Message : « '.$data['message'].' »' : ''), route('filament.admin.resources.dossiers.view', $dossier)));

        return back()->with('status', 'Merci, vos informations ont bien été enregistrées.');
    }

    private function authorizeDossier(Request $request, Dossier $dossier): void
    {
        abort_unless($dossier->client_id === $request->user('client')->id, 404);
    }
}
