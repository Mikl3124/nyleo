<?php

namespace Tests\Feature;

use App\Enums\DossierStatus;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Quote;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PortalFluidityTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_always_knows_what_to_do_next(): void
    {
        Notification::fake();
        $this->seed();
        $client = Client::create(['last_name' => 'Martin', 'first_name' => 'Anne', 'email' => 'a@example.com', 'birth_date' => '1950-01-01', 'birth_place' => 'Périgueux']);
        $calm = Dossier::create(['client_id' => $client->id, 'title' => 'Pergola', 'procedure' => 'DP', 'status' => DossierStatus::PlansEnCours, 'site_address' => '1 rue Haute', 'cadastre_number' => '12']);
        $toSign = Dossier::create(['client_id' => $client->id, 'title' => 'Carport', 'procedure' => 'DP']);
        $quote = Quote::create(['client_id' => $client->id, 'dossier_id' => $toSign->id]);
        $quote->lines()->create(['label' => 'DP', 'quantity' => 1, 'unit_price' => 400]);
        QuoteService::send($quote, notify: false);

        $this->actingAs($client, 'client');

        // Liste : le dossier qui attend le client passe en premier, avec ce qu'il doit faire.
        $this->get(route('portal.home'))->assertOk()
            ->assertSeeInOrder(['À faire : devis à signer', 'Carport', 'Pergola'])
            ->assertSee('Je dessine vos plans');

        // Rien à faire : une phrase rassurante.
        $this->get(route('portal.dossiers.show', $calm))->assertOk()
            ->assertSee('En ce moment')->assertSee('Rien à faire de votre côté');

        // Devis signé, pas encore de photos : l'envoi de photos devient l'action principale, en français.
        QuoteService::sign($quote->refresh(), 'Anne Martin', '127.0.0.1', 'test');
        $this->get(route('portal.dossiers.show', $toSign))->assertOk()
            ->assertSee('À faire : envoyer vos photos')
            ->assertSee('Choisir des photos ou des documents')
            ->assertDontSee('En ce moment');
    }
}
