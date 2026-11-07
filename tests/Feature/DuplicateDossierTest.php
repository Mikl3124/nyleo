<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Filament\Resources\Quotes\Pages\CreateQuote;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Un client qui a déjà son dossier ne doit pas en avoir un second à la création du devis. */
class DuplicateDossierTest extends TestCase
{
    use RefreshDatabase;

    private function client(): Client
    {
        return Client::create(['last_name' => 'Lafon', 'first_name' => 'Alain', 'type' => 'particulier', 'email' => 'lafon@example.com', 'city' => 'Ribérac', 'postal_code' => '24600']);
    }

    public function test_quote_without_dossier_goes_into_the_clients_current_dossier(): void
    {
        $client = $this->client();
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda — Ribérac', 'procedure' => 'DP']);

        $quote = Quote::create(['client_id' => $client->id, 'subject' => 'Déclaration préalable — véranda']);
        QuoteResource::ensureDossier($quote);

        $this->assertSame($dossier->id, $quote->fresh()->dossier_id);
        $this->assertSame(1, $client->dossiers()->count());
    }

    public function test_a_second_project_still_gets_its_own_dossier(): void
    {
        $client = $this->client();
        $first = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda', 'procedure' => 'DP']);
        Quote::create(['client_id' => $client->id, 'dossier_id' => $first->id, 'status' => QuoteStatus::Signe]);

        $quote = Quote::create(['client_id' => $client->id, 'subject' => 'Permis de construire — garage']);
        QuoteResource::ensureDossier($quote);

        $this->assertNotSame($first->id, $quote->fresh()->dossier_id);
        $this->assertSame(2, $client->dossiers()->count());
    }

    public function test_quote_form_preselects_the_current_dossier(): void
    {
        $this->actingAs(User::factory()->create());
        $client = $this->client();
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda — Ribérac', 'procedure' => 'DP']);

        Livewire::test(CreateQuote::class)
            ->set('data.client_id', $client->id)
            ->assertSet('data.dossier_id', $dossier->id);

        Livewire::withQueryParams(['client' => $client->id])->test(CreateQuote::class)
            ->assertSet('data.dossier_id', $dossier->id);
    }

    public function test_request_form_sent_twice_keeps_one_dossier(): void
    {
        $payload = ['last_name' => 'Bonneau', 'email' => 'bonneau@example.com', 'phone' => '0600000000',
            'procedure' => 'DP', 'nature' => 'veranda', 'site_city' => 'Bergerac (24100)', 'description' => 'Véranda de 20 m²'];

        $this->post(route('site.request'), $payload)->assertRedirect(route('site.request.thanks'));
        $this->post(route('site.request'), $payload)->assertRedirect(route('site.request.thanks'));

        $client = Client::where('email', 'bonneau@example.com')->sole();
        $this->assertSame(1, $client->dossiers()->count());
        $this->assertSame('Véranda de 20 m²', $client->dossiers()->sole()->description);
    }
}
