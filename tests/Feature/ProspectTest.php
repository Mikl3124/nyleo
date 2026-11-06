<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProspectTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_request_client_is_a_prospect_until_a_quote_is_signed(): void
    {
        $this->post('/demande', [
            'last_name' => 'Lolo', 'first_name' => 'Doudou', 'email' => 'doudou@example.com', 'phone' => '0785656565',
            'procedure' => 'DP', 'site_city' => 'Mussidan (24400)',
        ]);
        $client = Client::where('email', 'doudou@example.com')->firstOrFail();
        $other = Client::create(['last_name' => 'Martin', 'source' => 'telephone']);

        $this->assertTrue($client->isProspect());
        $this->assertFalse($other->isProspect());
        $this->assertEquals([$client->id], Client::prospects()->pluck('id')->all());

        $this->actingAs(User::factory()->create());
        $this->get('/admin/clients')->assertOk()->assertSee('Prospect');
        $this->get("/admin/clients/{$client->id}/edit")->assertOk()->assertSee('Demande de devis reçue par le site');
        Livewire::test(ListClients::class)->filterTable('prospect', true)
            ->assertCanSeeTableRecords([$client])->assertCanNotSeeTableRecords([$other]);

        Quote::create(['client_id' => $client->id, 'status' => QuoteStatus::Signe]);
        $this->assertFalse($client->fresh()->isProspect());
        $this->assertCount(0, Client::prospects()->get());
        $this->get('/admin/clients')->assertOk()->assertDontSee('>Prospect<', false);
    }
}
