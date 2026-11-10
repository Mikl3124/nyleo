<?php

namespace Tests\Feature;

use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Filament\Resources\Dossiers\Pages\ListDossiers;
use App\Filament\Resources\Dossiers\Pages\ViewDossier;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Quotes\Pages\EditQuote;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Document;
use App\Models\Dossier;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Quote;
use App\Models\User;
use App\Services\Deletion;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        $this->seed();
        $this->actingAs(User::factory()->create());
    }

    /** Un client complet : dossier, devis signé, facture émise et payée, document. */
    private function fullClient(string $name = 'Martin'): array
    {
        $client = Client::create(['last_name' => $name, 'email' => strtolower($name).'@example.com']);
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda', 'procedure' => 'DP']);
        $quote = Quote::create(['client_id' => $client->id, 'dossier_id' => $dossier->id]);
        $quote->lines()->create(['label' => 'DP', 'quantity' => 1, 'unit_price' => 400]);
        $quote->recalculate();
        $quote->forceFill(['number' => 'D202611-'.(643 + Quote::withTrashed()->count() - 1), 'status' => 'signe', 'signed_at' => now()])->save();
        $invoice = InvoiceService::issue(InvoiceService::createFinalFromQuote($quote), notify: false);
        InvoiceService::addPayment($invoice, 100, 'virement', notify: false);
        Storage::disk('local')->put("dossiers/{$dossier->id}/plan.pdf", 'pdf');
        Document::create(['dossier_id' => $dossier->id, 'name' => 'Plan', 'path' => "dossiers/{$dossier->id}/plan.pdf"]);
        $dossier->log('note', 'Appel');

        return [$client, $dossier, $quote, $invoice->refresh()];
    }

    public function test_warning_lists_everything_that_will_be_deleted(): void
    {
        [$client] = $this->fullClient();

        $text = Deletion::warning([$client]);

        $this->assertStringContainsString('1 client, 1 dossier, 1 devis, 1 facture ou avoir, 1 paiement enregistré et 1 document', $text);
        $this->assertStringContainsString('irréversible', $text);
        $this->assertStringContainsString('1 devis signé et 1 facture émise', $text);
    }

    public function test_deleting_a_client_removes_everything_but_keeps_the_partner(): void
    {
        [$client, $dossier, $quote, $invoice] = $this->fullClient();
        $partner = Partner::create(['name' => 'Thibault']);
        $client->update(['partner_id' => $partner->id]);

        Livewire::test(EditClient::class, ['record' => $client->id])
            ->callAction('deleteForever')
            ->assertRedirect('/admin/clients');

        $this->assertNull(Client::withTrashed()->find($client->id));
        $this->assertNull(Dossier::withTrashed()->find($dossier->id));
        $this->assertNull(Quote::withTrashed()->find($quote->id));
        $this->assertNull(Invoice::withTrashed()->find($invoice->id));
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('dossier_events', 0);
        $this->assertDatabaseCount('quote_lines', 0);
        Storage::disk('local')->assertMissing("dossiers/{$dossier->id}/plan.pdf");
        $this->assertNotNull($partner->fresh());
    }

    public function test_deleting_a_dossier_keeps_the_client(): void
    {
        [$client, $dossier] = $this->fullClient();

        Livewire::test(ViewDossier::class, ['record' => $dossier->id])
            ->callAction('deleteForever')
            ->assertRedirect('/admin/dossiers');

        $this->assertNull(Dossier::withTrashed()->find($dossier->id));
        $this->assertNotNull($client->fresh());
        $this->assertSame(0, $client->quotes()->withTrashed()->count());
        $this->assertSame(0, $client->invoices()->withTrashed()->count());
    }

    public function test_bulk_delete_needs_the_checkbox_then_removes_all_selected_clients(): void
    {
        $a = $this->fullClient('Martin')[0];
        $b = $this->fullClient('Durand')[0];
        $keep = Client::create(['last_name' => 'Garde']);

        Livewire::test(ListClients::class)
            ->callTableBulkAction('deleteForever', [$a, $b], data: ['confirm' => false])
            ->assertHasTableBulkActionErrors(['confirm']);
        $this->assertNotNull($a->fresh());

        Livewire::test(ListClients::class)
            ->callTableBulkAction('deleteForever', [$a, $b], data: ['confirm' => true])
            ->assertHasNoTableBulkActionErrors();

        $this->assertSame([$keep->id], Client::withTrashed()->pluck('id')->all());
        $this->assertDatabaseCount('dossiers', 0);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_bulk_delete_of_dossiers_and_invoices(): void
    {
        [, $d1] = $this->fullClient('Martin');
        [, $d2, , $inv2] = $this->fullClient('Durand');

        Livewire::test(ListDossiers::class)
            ->callTableBulkAction('deleteForever', [$d1], data: ['confirm' => true]);
        $this->assertNull(Dossier::withTrashed()->find($d1->id));
        $this->assertNotNull($d2->fresh());

        Livewire::test(ListInvoices::class)->assertTableBulkActionDoesNotExist('deleteForever');
        $this->assertNotNull(Invoice::withTrashed()->find($inv2->id));
        $this->assertNotNull($d2->fresh());
    }

    public function test_signed_quote_can_be_deleted_and_gives_its_number_back(): void
    {
        [, , $quote] = $this->fullClient();
        CompanySetting::current()->forceFill(['quote_next_number' => 644])->save();

        Livewire::test(EditQuote::class, ['record' => $quote->id])
            ->callAction('deleteForever')
            ->assertRedirect('/admin/quotes');

        $this->assertNull(Quote::withTrashed()->find($quote->id));
        $this->assertSame(643, (int) CompanySetting::current()->quote_next_number);
    }

    public function test_dossier_reference_stays_unique_after_a_deletion(): void
    {
        $client = Client::create(['last_name' => 'Martin']);
        $first = Dossier::create(['client_id' => $client->id, 'title' => 'A', 'procedure' => 'DP']);
        $second = Dossier::create(['client_id' => $client->id, 'title' => 'B', 'procedure' => 'DP']);
        Deletion::dossier($first);

        $third = Dossier::create(['client_id' => $client->id, 'title' => 'C', 'procedure' => 'DP']);

        $this->assertNotSame($second->reference, $third->reference);
    }
}
