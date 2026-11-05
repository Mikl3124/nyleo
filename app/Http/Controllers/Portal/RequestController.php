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

        return redirect()->route('home')->with('status', 'Merci ! Votre demande est bien reçue. Je vous rappelle sous 48 h ouvrées.')->withFragment('contact');
    }
}
