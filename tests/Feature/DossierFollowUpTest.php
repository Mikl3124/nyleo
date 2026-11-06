<?php

namespace Tests\Feature;

use App\Enums\DossierStatus;
use App\Enums\ExternalPaymentStatus;
use App\Enums\Priority;
use App\Enums\WaitingOn;
use App\Filament\Resources\Dossiers\Pages\ListDossiers;
use App\Filament\Resources\Dossiers\Pages\ViewDossier;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\User;
use App\Services\DossierWorkflow;
use App\Services\SuiviImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class DossierFollowUpTest extends TestCase
{
    use RefreshDatabase;

    private function dossier(array $attrs = []): Dossier
    {
        $client = Client::create(['last_name' => 'Martin', 'email' => 'm'.uniqid().'@example.com']);

        return Dossier::create(['client_id' => $client->id, 'title' => 'Véranda', 'procedure' => 'DP'] + $attrs);
    }

    public function test_follow_up_update_logs_a_dated_note(): void
    {
        $d = $this->dossier();
        DossierWorkflow::updateFollowUp($d, [
            'follow_up_action' => 'Relancer pour l\'état civil', 'waiting_on' => 'client',
            'follow_up_at' => now()->subDay()->toDateString(), 'priority' => 'urgente',
            'note' => str_repeat('Appelé le client. ', 30), 'note_visible' => true,
        ]);
        $d->refresh();

        $this->assertSame(WaitingOn::Client, $d->waiting_on);
        $this->assertSame(Priority::Urgente, $d->priority);
        $this->assertTrue($d->isFollowUpDue());
        $this->assertSame(1, Dossier::followUpDue()->count());
        $event = $d->events()->first();
        $this->assertSame('suivi', $event->type);
        $this->assertTrue($event->visible_to_client);
        $this->assertGreaterThan(255, strlen($event->message));
    }

    public function test_follow_up_without_note_logs_a_summary(): void
    {
        $d = $this->dossier();
        DossierWorkflow::updateFollowUp($d, ['follow_up_action' => 'Déposer', 'waiting_on' => 'moi']);
        $this->assertStringContainsString('À faire : Déposer', $d->events()->first()->message);
        $this->assertFalse($d->events()->first()->visible_to_client);
    }

    public function test_admin_screens_and_follow_up_action(): void
    {
        Notification::fake();
        $this->actingAs(User::factory()->create());
        $d = $this->dossier(['follow_up_action' => 'Appeler', 'follow_up_at' => now()->subDays(2), 'waiting_on' => 'moi',
            'prescriber' => 'AKENA — Rémi', 'commission_amount' => 100, 'drive_url' => 'https://drive.google.com/x', 'portal_url' => 'https://www.geopermis.fr/']);

        $this->get("/admin/dossiers/{$d->id}")->assertOk()->assertSee('Appeler')->assertSee('Ouvrir le Drive');
        $this->get("/admin/dossiers/{$d->id}/edit")->assertOk()->assertSee('Prochaine action');
        foreach (['tous', 'relancer', 'moi', 'client', 'mairie'] as $tab) {
            Livewire::test(ListDossiers::class, ['activeTab' => $tab])->assertOk();
        }
        Livewire::test(ListDossiers::class)->set('activeTab', 'relancer')->assertCanSeeTableRecords([$d]);
        Livewire::test(ListDossiers::class)->set('activeTab', 'client')->assertCanNotSeeTableRecords([$d]);

        Livewire::test(ViewDossier::class, ['record' => $d->getRouteKey()])
            ->callAction('followUp', ['follow_up_action' => 'Déposer en mairie', 'waiting_on' => 'mairie', 'priority' => 'normale', 'note' => 'Plans validés au téléphone'])
            ->assertHasNoActionErrors();
        $this->assertSame('Déposer en mairie', $d->refresh()->follow_up_action);
        $this->assertSame('Plans validés au téléphone', $d->events()->first()->message);
    }

    public function test_accorded_dossier_with_follow_up_stays_in_open_list(): void
    {
        $this->actingAs(User::factory()->create());
        $kept = $this->dossier(['status' => 'accorde', 'follow_up_at' => now()->addWeek()]);
        $closed = $this->dossier(['status' => 'accorde']);
        Livewire::test(ListDossiers::class)->assertCanSeeTableRecords([$kept])->assertCanNotSeeTableRecords([$closed]);
    }

    public function test_import_of_old_spreadsheet(): void
    {
        Notification::fake();
        $file = base_path('tests/fixtures/suivi-exemple.csv');

        $dry = SuiviImporter::import($file, dryRun: true);
        $this->assertSame(4, $dry['created']);
        $this->assertSame(0, Dossier::count());

        $r = SuiviImporter::import($file);
        $this->assertSame([], $r['errors']);
        $this->assertSame(4, $r['created']);
        $this->assertSame(4, $r['clients'], 'Les deux BRUNET de communes différentes restent deux clients');

        $a = Dossier::where('legacy_ref', 'URB-0001')->first();
        $this->assertSame(DossierStatus::Accorde, $a->status);
        $this->assertSame('AKENA — Rémi', $a->prescriber);
        $this->assertEquals(250, $a->commission_amount);
        $this->assertSame(ExternalPaymentStatus::Paye, $a->external_payment_status);
        $this->assertSame('Pessac', $a->site_city);
        $this->assertSame('33', $a->department);
        $this->assertSame('2025-09-16', $a->received_at->toDateString());
        $this->assertNull($a->follow_up_at);
        $this->assertStringContainsString('Dépôt (historique) : 06/11 - 11/12', $a->internal_notes);

        $b = Dossier::where('legacy_ref', 'URB-0002')->first();
        $this->assertSame(DossierStatus::EnInstruction, $b->status);
        $this->assertSame(WaitingOn::Moi, $b->waiting_on);
        $this->assertSame(Priority::Urgente, $b->priority);
        $this->assertTrue($b->commission_paid);
        $this->assertSame('2026-10-11', $b->deposit_date->toDateString());
        $this->assertNotNull($b->decision_due_date);

        $c = Dossier::where('legacy_ref', 'URB-0003')->first();
        $this->assertSame(DossierStatus::Refuse, $c->status);
        $this->assertSame('2026-11-02', $c->follow_up_at->toDateString(), 'Un refus à examiner garde sa relance');
        $this->assertNull($c->prescriber);

        // Relancer l'import met à jour sans doublon.
        $again = SuiviImporter::import($file);
        $this->assertSame(0, $again['created']);
        $this->assertSame(4, $again['updated']);
        $this->assertSame(4, Dossier::count());
        $this->assertSame(1, substr_count(Dossier::where('legacy_ref', 'URB-0001')->first()->internal_notes, 'Repris de l\'ancien suivi'));
        Notification::assertNothingSent();
    }

    public function test_portal_shows_instruction_dates(): void
    {
        $d = $this->dossier(['status' => 'en_instruction', 'deposit_date' => now()->subMonth(), 'decision_due_date' => now()->addMonth(),
            'instruction_started_at' => now()->subWeeks(3), 'incomplete_answered_at' => now()->subWeek(), 'follow_up_action' => 'Secret interne']);
        $this->actingAs($d->client, 'client')->get(route('portal.dossiers.show', $d))
            ->assertOk()->assertSee('Instruction commencée le')->assertSee('Pièces complémentaires envoyées')->assertDontSee('Secret interne');
    }
}
