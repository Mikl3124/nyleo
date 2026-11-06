<?php

namespace Tests\Feature;

use App\Enums\DossierStatus;
use App\Filament\Resources\Dossiers\Pages\ListDossiers;
use App\Filament\Resources\Dossiers\Pages\ViewDossier;
use App\Filament\Resources\Dossiers\RelationManagers\PieceRequestsRelationManager;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\User;
use App\Notifications\DossierUpdate;
use App\Services\DossierWorkflow;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PieceRequestTest extends TestCase
{
    use RefreshDatabase;

    private function dossier(): Dossier
    {
        $client = Client::create(['last_name' => 'Martin', 'email' => 'm@example.com']);

        return Dossier::create(['client_id' => $client->id, 'title' => 'Maison', 'procedure' => 'PC',
            'status' => 'en_instruction', 'deposit_date' => '2026-09-01', 'decision_due_date' => '2026-11-01']);
    }

    public function test_successive_requests_and_answers(): void
    {
        Notification::fake();
        $d = $this->dossier();

        DossierWorkflow::markIncomplete($d, Carbon::parse('2026-09-20'), 'PCMI4 notice', null, 'moi');
        DossierWorkflow::markIncomplete($d, Carbon::parse('2026-09-25'), 'Étude de sol', Carbon::parse('2026-11-30'), 'client');
        $d->refresh();
        $this->assertSame(DossierStatus::Incomplet, $d->status);
        $this->assertSame(2, $d->openPieceRequests()->count());
        $this->assertSame('2026-12-20', $d->pieceRequests()->where('pieces', 'PCMI4 notice')->first()->deadline->toDateString());
        Notification::assertSentTo($d->client, DossierUpdate::class);

        // Première réponse : une demande reste ouverte, le dossier reste « pièces demandées ».
        DossierWorkflow::markCompleted($d, Carbon::parse('2026-10-01'), $d->pieceRequests()->where('pieces', 'PCMI4 notice')->first());
        $d->refresh();
        $this->assertSame(DossierStatus::Incomplet, $d->status);
        $this->assertSame('2026-11-30', $d->incomplete_deadline->toDateString());

        // Dernière réponse : retour en instruction, délai recalculé.
        DossierWorkflow::markCompleted($d, Carbon::parse('2026-10-10'));
        $d->refresh();
        $this->assertSame(DossierStatus::EnInstruction, $d->status);
        $this->assertSame(0, $d->openPieceRequests()->count());
        $this->assertSame('2026-12-10', $d->decision_due_date->toDateString());
    }

    public function test_admin_actions_tabs_and_client_timeline(): void
    {
        Notification::fake();
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $d = $this->dossier();

        Livewire::test(ViewDossier::class, ['record' => $d->getRouteKey()])
            ->callAction('incomplete', ['requested_at' => '2026-10-27', 'deadline' => '2027-01-27', 'pieces' => 'Attestation RE2020 signée', 'waiting_on' => 'client', 'notify' => false])
            ->assertHasNoActionErrors();
        $pr = $d->pieceRequests()->first();
        $this->assertSame('client', $pr->waiting_on->value);
        Notification::assertNothingSent();

        $this->get("/admin/dossiers/{$d->id}")->assertOk();
        Livewire::test(PieceRequestsRelationManager::class, ['ownerRecord' => $d, 'pageClass' => ViewDossier::class])
            ->assertOk()->assertCanSeeTableRecords([$pr]);
        Livewire::test(ListDossiers::class)->set('activeTab', 'client')->assertCanSeeTableRecords([$d]);
        Livewire::test(ListDossiers::class)->set('activeTab', 'moi')->assertCanNotSeeTableRecords([$d]);

        $this->actingAs($d->client, 'client')->get(route('portal.dossiers.show', $d))
            ->assertOk()->assertSee('Attestation RE2020 signée')->assertSee('besoin de documents de votre part', false)
            ->assertSee('Envoyer mes documents');

        Livewire::test(PieceRequestsRelationManager::class, ['ownerRecord' => $d, 'pageClass' => ViewDossier::class])
            ->callTableAction('sendPieces', $pr, ['date' => '2026-11-05'])->assertHasNoTableActionErrors();
        $this->assertSame('2026-11-05', $pr->refresh()->sent_at->toDateString());
        $this->assertSame(DossierStatus::EnInstruction, $d->refresh()->status);
        Livewire::test(ListDossiers::class)->set('activeTab', 'client')->assertCanNotSeeTableRecords([$d]);
        $this->get(route('portal.dossiers.show', $d))->assertSee('Envoyées le 05/11/2026');
    }
}
