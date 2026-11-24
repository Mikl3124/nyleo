<?php

namespace Tests\Feature;

use App\Filament\Resources\Dossiers\DossierResource;
use App\Filament\Resources\Dossiers\Pages\ViewDossier;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\User;
use App\Notifications\AdminAlert;
use App\Services\DossierWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Fichiers envoyés par le client : e-mail à Mickael, visibles en haut du dossier, lien toujours valable. */
class ClientUploadsAdminTest extends TestCase
{
    use RefreshDatabase;

    private function dossier(): Dossier
    {
        $client = Client::create(['last_name' => 'Bonneau', 'first_name' => 'Marie', 'type' => 'particulier', 'email' => 'bonneau@example.com']);

        return Dossier::create(['client_id' => $client->id, 'title' => 'Abri de jardin', 'procedure' => 'DP']);
    }

    public function test_upload_emails_the_admin_with_the_files_attached(): void
    {
        Storage::fake('local');
        Notification::fake();
        $admin = User::factory()->create();
        $dossier = $this->dossier();
        $this->actingAs($dossier->client, 'client');

        $this->post(route('portal.dossiers.upload', $dossier), ['files' => [
            UploadedFile::fake()->image('facade.jpg'),
            UploadedFile::fake()->create('cadastre.pdf', 100, 'application/pdf'),
        ]])->assertRedirect();

        Notification::assertSentTo($admin, AdminAlert::class, function (AdminAlert $alert) use ($admin, $dossier) {
            $this->assertContains('mail', $alert->via($admin));
            $this->assertSame(['facade.jpg', 'cadastre.pdf'], array_column($alert->files, 'name'));
            $this->assertSame(route('filament.admin.resources.dossiers.view', $dossier), $alert->url);

            $mail = $alert->toMail($admin);
            $this->assertInstanceOf(MailMessage::class, $mail);
            $this->assertCount(2, $mail->attachments);
            $this->assertSame('Voir les fichiers', $mail->actionText);
            $this->assertContains('Les fichiers sont joints à cet e-mail.', $mail->introLines);

            return true;
        });
    }

    public function test_birth_info_entered_before_the_plans_emails_the_admin(): void
    {
        Notification::fake();
        $admin = User::factory()->create();
        $dossier = $this->dossier();
        $plan = $dossier->documents()->create(['category' => 'plan', 'name' => 'Plans DP', 'path' => 'x.pdf', 'uploaded_by' => 'admin', 'visible_to_client' => true]);
        $this->actingAs($dossier->client, 'client');

        $this->post(route('portal.documents.birth', $plan), ['birth_date' => '1960-04-12', 'birth_place' => 'Périgueux'])->assertRedirect();

        Notification::assertSentTo($admin, AdminAlert::class, fn (AdminAlert $a) => str_contains($a->body, '12/04/1960, Périgueux') && in_array('mail', $a->via($admin), true));
    }

    public function test_dossier_page_shows_client_files_and_opens_them(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $dossier = $this->dossier();
        Storage::disk('local')->put("dossiers/{$dossier->id}/facade.jpg", 'img');
        $doc = $dossier->documents()->create(['category' => 'photo', 'name' => 'facade.jpg', 'original_name' => 'facade.jpg',
            'path' => "dossiers/{$dossier->id}/facade.jpg", 'mime_type' => 'image/jpeg', 'uploaded_by' => 'client']);

        Livewire::test(ViewDossier::class, ['record' => $dossier->getRouteKey()])
            ->assertSee('Envoyé par le client')
            ->assertSee('facade.jpg')
            ->assertSee(route('admin.documents.download', $doc), false);

        $this->get(route('admin.documents.download', $doc))->assertOk()->assertHeader('content-disposition', 'inline; filename=facade.jpg');
        $this->get(route('admin.documents.download', [$doc, 'telecharger' => 1]))->assertOk()->assertHeader('content-disposition', 'attachment; filename=facade.jpg');
    }

    public function test_section_is_hidden_without_client_files(): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test(ViewDossier::class, ['record' => $this->dossier()->getRouteKey()])->assertDontSee('Envoyé par le client');
    }

    public function test_link_to_a_merged_dossier_opens_the_dossier_that_absorbed_it(): void
    {
        $this->actingAs(User::factory()->create());
        $duplicate = $this->dossier();
        $keep = Dossier::create(['client_id' => $duplicate->client_id, 'title' => 'Abri', 'procedure' => 'DP']);
        Dossier::create(['client_id' => $duplicate->client_id, 'title' => 'Autre projet', 'procedure' => 'PC']);
        DossierWorkflow::merge($keep, $duplicate);

        $this->get(DossierResource::getUrl('view', ['record' => $duplicate->id]))
            ->assertRedirect(DossierResource::getUrl('view', ['record' => $keep->id]));
    }

    public function test_unknown_dossier_is_still_not_found(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(DossierResource::getUrl('view', ['record' => 9999]))->assertNotFound();
    }
}
