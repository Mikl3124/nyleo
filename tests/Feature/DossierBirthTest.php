<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DossierBirthTest extends TestCase
{
    use RefreshDatabase;

    public function test_birth_date_and_place_are_shown_when_filled(): void
    {
        $client = Client::create(['last_name' => 'Martin', 'email' => 'm@example.com', 'birth_date' => '1980-03-12', 'birth_place' => 'Périgueux']);
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Pergola', 'procedure' => 'DP']);

        $this->actingAs(User::factory()->create())
            ->get("/admin/dossiers/{$dossier->id}")
            ->assertOk()->assertSee('Naissance')->assertSee('12/03/1980')->assertSee('Périgueux');
    }

    public function test_birth_line_is_hidden_when_empty(): void
    {
        $client = Client::create(['last_name' => 'Martin', 'email' => 'm@example.com']);
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Pergola', 'procedure' => 'DP']);

        $this->actingAs(User::factory()->create())
            ->get("/admin/dossiers/{$dossier->id}")
            ->assertOk()->assertDontSee('Naissance');
    }
}
