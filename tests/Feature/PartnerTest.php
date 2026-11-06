<?php

namespace Tests\Feature;

use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Dossiers\Pages\ViewDossier;
use App\Filament\Resources\Partners\Pages\ListPartners;
use App\Filament\Resources\Partners\Pages\ViewPartner;
use App\Filament\Resources\Partners\RelationManagers\DossiersRelationManager;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Partner;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerTest extends TestCase
{
    use RefreshDatabase;

    private function partner(array $attrs = []): Partner
    {
        return Partner::create($attrs + ['name' => 'Thibault', 'company' => 'AKENA', 'commission_type' => 'percent', 'commission_value' => 10]);
    }

    public function test_dossier_inherits_the_client_partner_and_computes_the_commission(): void
    {
        $partner = $this->partner();
        $client = Client::create(['last_name' => 'Martin', 'source' => 'partenaire', 'partner_id' => $partner->id]);
        $d = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda', 'procedure' => 'DP']);

        $this->assertSame($partner->id, $d->partner_id);
        $this->assertNull($d->commissionDue(), 'Sans montant, pas de commission en pourcentage');

        Quote::create(['client_id' => $client->id, 'dossier_id' => $d->id, 'status' => 'signe', 'total_ht' => 450, 'total_ttc' => 450]);
        $this->assertSame(450.0, $d->amountHt());
        $this->assertSame(45.0, $d->commissionDue());
        $this->assertTrue($d->isCommissionComputed());

        $d->update(['commission_amount' => 60]);
        $this->assertSame(60.0, $d->fresh()->commissionDue());

        $d->update(['commissionable' => false]);
        $this->assertNull($d->fresh()->commissionDue());
    }

    public function test_totals_and_paid_date(): void
    {
        $partner = $this->partner(['commission_type' => 'fixed', 'commission_value' => 50]);
        $client = Client::create(['last_name' => 'Durand']);
        $a = Dossier::create(['client_id' => $client->id, 'partner_id' => $partner->id, 'title' => 'A', 'procedure' => 'DP', 'agreed_price' => 400]);
        Dossier::create(['client_id' => $client->id, 'partner_id' => $partner->id, 'title' => 'B', 'procedure' => 'PC', 'agreed_price' => 650]);
        Dossier::create(['client_id' => $client->id, 'partner_id' => $partner->id, 'title' => 'C', 'procedure' => 'DP', 'commissionable' => false]);

        $a->update(['commission_paid' => true]);
        $this->assertTrue($a->fresh()->commission_paid_at->isToday());

        $t = $partner->totals();
        $this->assertSame(3, $t['count']);
        $this->assertSame(2, $t['commissioned_count']);
        $this->assertEquals(1050, $t['amount']);
        $this->assertEquals(100, $t['commission']);
        $this->assertEquals(50, $t['paid']);
        $this->assertEquals(50, $t['to_pay']);
    }

    public function test_choosing_a_partner_on_a_client_attaches_its_dossiers(): void
    {
        $partner = $this->partner();
        $client = Client::create(['last_name' => 'Petit']);
        $d = Dossier::create(['client_id' => $client->id, 'title' => 'Carport', 'procedure' => 'DP']);
        $this->assertNull($d->partner_id);

        $client->update(['source' => 'partenaire', 'partner_id' => $partner->id]);
        $this->assertSame($partner->id, $d->fresh()->partner_id);
    }

    public function test_admin_screens(): void
    {
        $this->actingAs(User::factory()->create());
        $partner = $this->partner();
        $client = Client::create(['last_name' => 'Martin', 'source' => 'partenaire', 'partner_id' => $partner->id]);
        $d = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda', 'procedure' => 'DP', 'agreed_price' => 500]);

        Livewire::test(ListPartners::class)->assertOk()->assertSee('Thibault')->assertSee('50,00');
        Livewire::test(ViewPartner::class, ['record' => $partner->id])->assertOk()->assertSee('Reste à payer');
        Livewire::test(DossiersRelationManager::class, ['ownerRecord' => $partner, 'pageClass' => ViewPartner::class])
            ->assertOk()->assertSee('Véranda')
            ->callTableBulkAction('markPaid', [$d], ['paid_at' => '2026-11-01']);
        $this->assertTrue($d->fresh()->commission_paid);
        $this->assertSame('2026-11-01', $d->fresh()->commission_paid_at->toDateString());

        Livewire::test(ViewDossier::class, ['record' => $d->id])->assertOk()->assertSee('Thibault');
        Livewire::test(EditClient::class, ['record' => $client->id])->assertOk()->assertSee('Partenaire');

        $this->get('/admin/partners')->assertOk();
        $this->get('/admin/partners/'.$partner->id)->assertOk();
        $this->get('/admin/partners/'.$partner->id.'/edit')->assertOk();
    }

    public function test_partner_can_be_created_from_the_client_form(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::create(['last_name' => 'Martin', 'source' => 'partenaire']);

        Livewire::test(EditClient::class, ['record' => $client->id])
            ->mountFormComponentAction('partner_id', 'createOption')
            ->setFormComponentActionData(['name' => 'Rémi', 'company' => 'AKENA', 'commission_type' => 'fixed', 'commission_value' => 40])
            ->callMountedFormComponentAction()
            ->assertHasNoFormComponentActionErrors();

        $this->assertDatabaseHas('partners', ['name' => 'Rémi', 'commission_type' => 'fixed']);
    }
}
