<?php

namespace Tests\Feature;

use App\Enums\DossierStatus;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Quote;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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
            ->assertSeeInOrder(['devis à signer', 'Carport', 'Pergola'])
            ->assertSee('Je dessine vos plans');

        // Rien à faire : une phrase rassurante.
        $this->get(route('portal.dossiers.show', $calm))->assertOk()
            ->assertSee('En ce moment')->assertSee('Rien à faire de votre côté');

        // Devis signé : on passe directement aux plans. L'envoi de photos reste proposé, mais facultatif.
        QuoteService::sign($quote->refresh(), 'Anne Martin', '127.0.0.1', 'test');
        $this->get(route('portal.dossiers.show', $toSign))->assertOk()
            ->assertSee('Réalisation des plans')
            ->assertDontSee('Vos documents')
            ->assertDontSee('envoyer vos photos')
            ->assertSee('Facultatif')
            ->assertSee('Glissez vos photos ou documents ici')
            ->assertSee('Prendre une photo');
    }

    public function test_drag_and_drop_upload_answers_in_json(): void
    {
        Storage::fake('local');
        Notification::fake();
        $this->seed();
        $client = Client::create(['last_name' => 'Martin', 'email' => 'a@example.com']);
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Pergola', 'procedure' => 'DP', 'status' => DossierStatus::PlansEnCours]);
        $other = Dossier::create(['client_id' => Client::create(['last_name' => 'Autre', 'email' => 'o@example.com'])->id, 'title' => 'X', 'procedure' => 'DP']);
        $this->actingAs($client, 'client');

        $this->postJson(route('portal.dossiers.upload', $dossier), ['files' => [
            UploadedFile::fake()->image('maison.jpg'),
            UploadedFile::fake()->create('cadastre.pdf', 100, 'application/pdf'),
        ]])->assertOk()->assertJson(['message' => 'Merci, vos 2 fichiers ont bien été envoyés.']);
        $this->assertSame(2, $dossier->documents()->count());
        // Le message s'affiche après le rechargement de la page.
        $this->get(route('portal.dossiers.show', $dossier))->assertSee('vos 2 fichiers ont bien été envoyés');

        $this->postJson(route('portal.dossiers.upload', $dossier), ['files' => [UploadedFile::fake()->create('virus.exe', 10)]])
            ->assertStatus(422)->assertJsonPath('errors', fn ($e) => str_contains(json_encode($e, JSON_UNESCAPED_UNICODE), 'Seules les photos et les PDF'));
        $this->postJson(route('portal.dossiers.upload', $other), ['files' => [UploadedFile::fake()->image('a.jpg')]])->assertNotFound();
    }
}
