<?php

namespace Tests\Feature;

use App\Filament\Resources\Dossiers\Pages\ViewDossier;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Quote;
use App\Models\User;
use App\Services\DossierWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Deux dossiers pour le même projet : on les fusionne en un seul. */
class MergeDossierTest extends TestCase
{
    use RefreshDatabase;

    private function client(): Client
    {
        return Client::create(['last_name' => 'Lafon', 'first_name' => 'Alain', 'type' => 'particulier', 'email' => 'lafon@example.com']);
    }

    public function test_merge_moves_everything_into_the_kept_dossier(): void
    {
        $client = $this->client();
        $keep = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda — Ribérac', 'procedure' => 'DP', 'site_city' => 'Ribérac']);
        $keep->log('demande_site', 'Demande reçue depuis le site nyleo.fr');
        $duplicate = Dossier::create(['client_id' => $client->id, 'title' => 'Déclaration préalable — véranda', 'procedure' => 'DP', 'description' => 'Véranda de 18 m²']);
        $duplicate->log('creation', 'Dossier créé avec le devis');
        $quote = Quote::create(['client_id' => $client->id, 'dossier_id' => $duplicate->id]);

        DossierWorkflow::merge($keep, $duplicate);

        $this->assertSame($keep->id, $quote->fresh()->dossier_id);
        $this->assertSoftDeleted($duplicate);
        $this->assertSame(1, $client->dossiers()->count());
        $keep->refresh();
        $this->assertSame('Véranda — Ribérac', $keep->title);
        $this->assertSame('Véranda de 18 m²', $keep->description);
        $this->assertSame(['fusion', 'creation', 'demande_site'], $keep->events()->reorder('id', 'desc')->pluck('type')->all());
    }

    public function test_dossiers_of_different_clients_cannot_be_merged(): void
    {
        $a = Dossier::create(['client_id' => $this->client()->id, 'title' => 'A', 'procedure' => 'DP']);
        $b = Dossier::create(['client_id' => Client::create(['last_name' => 'Bonneau', 'type' => 'particulier'])->id, 'title' => 'B', 'procedure' => 'DP']);

        $this->expectException(\InvalidArgumentException::class);
        DossierWorkflow::merge($a, $b);
    }

    public function test_merge_action_on_the_dossier_page(): void
    {
        $this->actingAs(User::factory()->create());
        $client = $this->client();
        $keep = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda', 'procedure' => 'DP']);
        $duplicate = Dossier::create(['client_id' => $client->id, 'title' => 'Doublon', 'procedure' => 'DP']);

        Livewire::test(ViewDossier::class, ['record' => $keep->id])
            ->callAction('merge', ['duplicate_id' => $duplicate->id])
            ->assertHasNoActionErrors();

        $this->assertSoftDeleted($duplicate);
    }
}
