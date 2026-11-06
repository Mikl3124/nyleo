<?php

namespace App\Http\Controllers\Portal;

use App\Filament\Resources\Dossiers\DossierResource;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Dossier;
use App\Notifications\AdminAlert;
use App\Support\Admin;
use Illuminate\Http\Request;

/** Formulaire de demande de devis du site nyleo.fr : crée le client et le dossier. */
class RequestController extends Controller
{
    public function store(Request $request)
    {
        if (filled($request->input('website'))) { // pot de miel anti-robots
            return back();
        }

        $data = $request->validate([
            'last_name' => ['required', 'string', 'max:120'],
            'first_name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:30'],
            'procedure' => ['required', 'in:DP,PC,AUTRE'],
            'nature' => ['nullable', 'string', 'max:60'],
            'site_city' => ['required', 'string', 'max:120'],
            'site_postal_code' => ['nullable', 'string', 'max:10'],
            'description' => ['nullable', 'string', 'max:3000'],
        ], [], [
            'last_name' => 'nom', 'phone' => 'téléphone', 'site_city' => 'commune du projet', 'procedure' => 'type de demande',
        ]);

        // « Mussidan (24400) » choisi dans la liste : la commune et le code postal sont séparés, même sans JavaScript.
        if (preg_match('/^(.*\S)\s*\((\d{5})\)\s*$/u', $data['site_city'], $m)) {
            $data['site_city'] = $m[1];
            $data['site_postal_code'] = $m[2];
        }

        $client = Client::firstOrCreate(['email' => $data['email']], [
            'last_name' => $data['last_name'], 'first_name' => $data['first_name'] ?? null, 'phone' => $data['phone'], 'source' => 'site',
        ]);

        $dossier = Dossier::create([
            'client_id' => $client->id,
            'title' => (DossierResource::NATURES[$data['nature'] ?? ''] ?? 'Projet').' — '.$data['site_city'],
            'procedure' => $data['procedure'],
            'nature' => $data['nature'] ?? null,
            'site_city' => $data['site_city'],
            'site_postal_code' => $data['site_postal_code'] ?? null,
            'description' => $data['description'] ?? null,
        ]);
        $dossier->log('demande_site', 'Demande reçue depuis le site nyleo.fr');

        Admin::notify(new AdminAlert('Nouvelle demande de devis', "{$client->display_name} ({$data['phone']}) — {$dossier->title}",
            route('filament.admin.resources.dossiers.view', $dossier)));

        // Écran de confirmation dédié : le récapitulatif reste affiché si la personne recharge la page.
        session()->put('demande_recue', [
            'name' => trim(($data['first_name'] ?? '').' '.$data['last_name']),
            'first_name' => $data['first_name'] ?? null,
            'nature' => $data['nature'] ?? null,
            'procedure' => $data['procedure'],
            'city' => $data['site_city'].(filled($data['site_postal_code'] ?? null) ? ' ('.$data['site_postal_code'].')' : ''),
            'phone' => $data['phone'],
            'email' => $data['email'],
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->route('site.request.thanks');
    }

    /** Écran « Merci » affiché après l'envoi du formulaire. */
    public function thanks()
    {
        $demande = session('demande_recue');
        if (! is_array($demande)) {
            return redirect()->route('site.contact');
        }

        return view('site.thanks', ['d' => $demande]);
    }
}
