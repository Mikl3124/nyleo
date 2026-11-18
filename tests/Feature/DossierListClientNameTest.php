<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DossierListClientNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_name_puts_last_name_first_in_capitals(): void
    {
        $client = new Client(['first_name' => 'Gilles', 'last_name' => 'Lefort']);
        $this->assertSame('LEFORT Gilles', $client->list_name);

        $client = new Client(['first_name' => 'Hélène', 'last_name' => 'Lévêque']);
        $this->assertSame('LÉVÊQUE Hélène', $client->list_name);

        $pro = new Client(['type' => 'professionnel', 'company_name' => 'Akena Vérandas', 'last_name' => 'Durand']);
        $this->assertSame('AKENA VÉRANDAS', $pro->list_name);
    }

    public function test_dossier_list_shows_client_name_then_project(): void
    {
        $client = Client::create(['first_name' => 'Gilles', 'last_name' => 'Lefort', 'email' => 'g@example.com']);
        Dossier::create(['client_id' => $client->id, 'title' => 'Pergola bois', 'procedure' => 'DP', 'site_city' => 'Lacanau']);

        $this->actingAs(User::factory()->create())
            ->get('/admin/dossiers')
            ->assertOk()
            ->assertSeeInOrder(['LEFORT Gilles', 'Pergola bois', 'Lacanau']);
    }
}
