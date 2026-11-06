<?php

namespace Tests\Feature;

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Dossiers\Pages\EditDossier;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectAddressTest extends TestCase
{
    use RefreshDatabase;

    private function client(): Client
    {
        return Client::create(['last_name' => 'Lolo', 'type' => 'particulier', 'address' => '3 rue des Lilas', 'postal_code' => '24400', 'city' => 'Mussidan']);
    }

    public function test_same_as_client_copies_and_follows_the_client_address(): void
    {
        $client = $this->client();
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda', 'procedure' => 'DP', 'site_same_as_client' => true]);

        $this->assertSame('3 rue des Lilas', $dossier->site_address);
        $this->assertSame('Mussidan', $dossier->site_city);
        $this->assertSame('24', $dossier->department);

        $client->update(['address' => '8 place du Marché', 'postal_code' => '33500', 'city' => 'Libourne']);
        $dossier->refresh();
        $this->assertSame('8 place du Marché', $dossier->site_address);
        $this->assertSame('Libourne', $dossier->site_city);
        $this->assertSame('33', $dossier->department);
    }

    public function test_client_form_edits_the_current_dossier_address(): void
    {
        $this->actingAs(User::factory()->create());
        $client = $this->client();
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda', 'procedure' => 'DP']);

        Livewire::test(EditClient::class, ['record' => $client->getRouteKey()])
            ->assertSee('Adresse du projet')
            ->assertSee('Identique à l')
            ->fillForm(['currentDossier' => ['site_same_as_client' => false, 'site_address' => 'Lieu-dit Les Vignes', 'site_postal_code' => '24130', 'site_city' => 'Prigonrieux', 'cadastre_section' => 'AB', 'cadastre_number' => '756']])
            ->call('save')
            ->assertHasNoFormErrors();

        $dossier->refresh();
        $this->assertSame('Lieu-dit Les Vignes', $dossier->site_address);
        $this->assertSame('Prigonrieux', $dossier->site_city);
        $this->assertSame('756', $dossier->cadastre_number);

        Livewire::test(EditClient::class, ['record' => $client->getRouteKey()])
            ->fillForm(['currentDossier' => ['site_same_as_client' => true]])
            ->assertFormFieldIsHidden('currentDossier.site_address')
            ->call('save')
            ->assertHasNoFormErrors();

        $dossier->refresh();
        $this->assertTrue($dossier->site_same_as_client);
        $this->assertSame('3 rue des Lilas', $dossier->site_address);
        $this->assertSame('AB', $dossier->cadastre_section);
        $this->assertSame(1, Dossier::count());
    }

    public function test_new_client_without_project_address_creates_no_dossier(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateClient::class)
            ->fillForm(['type' => 'particulier', 'last_name' => 'Sans projet'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('clients', ['last_name' => 'Sans projet']);
        $this->assertSame(0, Dossier::count());
    }

    public function test_new_client_with_same_address_gets_a_dossier(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateClient::class)
            ->fillForm(['type' => 'particulier', 'last_name' => 'Martin', 'address' => '1 rue Haute', 'postal_code' => '24000', 'city' => 'Périgueux',
                'currentDossier' => ['site_same_as_client' => true]])
            ->call('create')
            ->assertHasNoFormErrors();

        $dossier = Dossier::sole();
        $this->assertSame('Projet — Périgueux', $dossier->title);
        $this->assertSame('1 rue Haute', $dossier->site_address);
        $this->assertTrue($dossier->site_same_as_client);
    }

    public function test_dossier_form_has_the_same_checkbox(): void
    {
        $this->actingAs(User::factory()->create());
        $client = $this->client();
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda', 'procedure' => 'DP', 'site_city' => 'Bergerac']);

        Livewire::test(EditDossier::class, ['record' => $dossier->getRouteKey()])
            ->assertSee('Identique à l')
            ->fillForm(['site_same_as_client' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Mussidan', $dossier->refresh()->site_city);
    }
}
