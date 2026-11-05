<?php

namespace Database\Seeders;

use App\Enums\DocumentCategory;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Dossier;
use App\Models\Quote;
use App\Models\User;
use App\Services\DossierWorkflow;
use App\Services\QuoteService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/** Données fictives pour essayer l'application en local : php artisan db:seed --class=DemoSeeder */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        Notification::fake();
        User::firstOrCreate(['email' => 'demo@nyleo.fr'], ['name' => 'Démo', 'password' => 'demo-nyleo']);
        CompanySetting::current()->update([
            'company_name' => 'Mickaël Delpech', 'legal_form' => 'Entrepreneur individuel', 'siret' => '000 000 000 00000',
            'address' => '1 rue de l\'Exemple', 'postal_code' => '24700', 'city' => 'Montpon-Ménestérol', 'email' => 'contact@nyleo.fr',
            'iban' => 'FR76 1234 5678 9012 3456 7890 123', 'bic' => 'QNTOFRP1XXX', 'cheque_order' => 'Mickaël Delpech',
        ]);

        $c1 = Client::firstOrCreate(['email' => 'jm.simonet@example.com'], ['civility' => 'M.', 'first_name' => 'Jean-Marc', 'last_name' => 'Simonet', 'phone' => '06 00 00 00 01', 'address' => '12 route des Vignes', 'postal_code' => '24600', 'city' => 'Ribérac']);
        $d1 = Dossier::create(['client_id' => $c1->id, 'title' => 'Véranda 18 m²', 'procedure' => 'DP', 'nature' => 'veranda', 'site_address' => '12 route des Vignes', 'site_postal_code' => '24600', 'site_city' => 'Ribérac']);
        $q1 = Quote::create(['client_id' => $c1->id, 'dossier_id' => $d1->id, 'subject' => 'Déclaration préalable — véranda']);
        $q1->lines()->create(['label' => 'Déclaration préalable — véranda', 'description' => "Plans de l'existant et du projet, insertion graphique, CERFA, dépôt et suivi.", 'quantity' => 1, 'unit_price' => 400]);
        $q1->recalculate();
        QuoteService::send($q1);
        QuoteService::sign($q1->refresh(), 'Jean-Marc Simonet', '90.1.2.3', 'demo');
        Storage::disk('local')->put("dossiers/{$d1->id}/V1-DP-SIMONET.pdf", '%PDF-1.4 demo');
        $plan = $d1->documents()->create(['category' => DocumentCategory::Plan, 'name' => 'Plans DP véranda', 'path' => "dossiers/{$d1->id}/V1-DP-SIMONET.pdf"]);
        DossierWorkflow::submitPlans($plan, 'Voici la première version.');

        $c2 = Client::firstOrCreate(['email' => 'stan@example.com'], ['first_name' => 'Stan', 'last_name' => 'Markovic', 'phone' => '06 00 00 00 02', 'postal_code' => '33110', 'city' => 'Le Bouscat']);
        $d2 = Dossier::create(['client_id' => $c2->id, 'title' => 'Véranda et portail', 'procedure' => 'DP', 'nature' => 'veranda', 'site_postal_code' => '33110', 'site_city' => 'Le Bouscat']);
        $q2 = Quote::create(['client_id' => $c2->id, 'dossier_id' => $d2->id, 'subject' => 'Déclaration préalable — véranda et portail', 'deposit_percent' => 30]);
        $q2->lines()->create(['label' => 'Déclaration préalable — véranda', 'quantity' => 1, 'unit_price' => 400]);
        $q2->lines()->create(['label' => 'Portail et accès véhicule', 'quantity' => 1, 'unit_price' => 80]);
        $q2->recalculate();
        QuoteService::send($q2);

        $c3 = Client::firstOrCreate(['email' => 'contact@hoffmann.example'], ['type' => 'professionnel', 'company_name' => 'Entreprise Hoffmann', 'last_name' => 'Hoffmann', 'city' => 'Chalais']);
        $d3 = Dossier::create(['client_id' => $c3->id, 'title' => 'Maison individuelle — Chalais', 'procedure' => 'PC', 'nature' => 'maison', 'site_postal_code' => '24800', 'site_city' => 'Chalais', 'status' => 'en_instruction', 'deposit_date' => now()->subMonths(1), 'decision_due_date' => now()->addMonth()]);
        $d3->log('depose', 'Dossier déposé en mairie', true);
    }
}
