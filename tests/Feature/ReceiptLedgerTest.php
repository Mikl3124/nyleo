<?php

namespace Tests\Feature;

use App\Filament\Pages\Accounting;
use App\Models\Client;
use App\Models\ExternalBillingAccount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ReceiptEntry;
use App\Models\User;
use App\Services\Billing\ReceiptLedger;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ReceiptLedgerTest extends TestCase
{
    use RefreshDatabase;

    private function doc(int $id, int $amount, string $date): array
    {
        return ['id' => $id, 'number' => 'FAC-'.$id, 'client_name' => 'Client '.$id, 'paid_minor' => $amount, 'vat_minor' => 0, 'type' => 'invoice', 'issuer' => ['company_siret' => '82503053900035'], 'paid_date' => $date, 'stripe_payment_intent_id' => 'pi_'.$id, 'payments' => []];
    }

    private function stripe(): void
    {
        ExternalBillingAccount::create(['source' => 'permis-construire.com', 'payload' => ['checked_at' => now()->toIso8601String(), 'transactions_complete' => true, 'transactions' => []], 'source_exported_at' => now()->toIso8601String()]);
    }

    public function test_receipts_use_payment_month_gross_amount_and_idempotent_keys(): void
    {
        $l = app(ReceiptLedger::class);
        $docs = [$this->doc(7, 128700, '2026-08-10'), $this->doc(8, 47500, '2026-08-12'), $this->doc(10, 62500, '2026-10-24')];
        $l->external($docs);
        $l->external($docs);
        ExternalBillingAccount::create(['source' => 'permis-construire.com', 'source_exported_at' => now()->toIso8601String(), 'payload' => ['checked_at' => now()->toIso8601String(), 'transactions_complete' => true, 'transactions' => [
            ['type' => 'charge', 'currency' => 'EUR', 'created' => Carbon::parse('2026-10-24', 'Europe/Paris')->timestamp, 'fee_minor' => 963, 'net_minor' => 61537],
            ['type' => 'stripe_fee', 'currency' => 'EUR', 'created' => Carbon::parse('2026-10-24', 'Europe/Paris')->timestamp, 'fee_minor' => 0, 'net_minor' => -5],
        ]]]);
        $this->assertSame(968, $l->period('2026-10')['stripe_fees_minor']);
        $this->assertDatabaseCount('receipt_entries', 3);
        $this->assertSame(176200, $l->period('2026-08')['base_minor']);
        $this->assertSame(62500, $l->period('2026-10')['base_minor']);
        $this->assertSame(0, $l->period('2026-11')['base_minor']);
        $changed = $docs[0];
        $changed['paid_minor'] = 120000;
        $l->external([$changed]);
        $this->assertSame(176200, $l->period('2026-08')['base_minor']);
        $this->assertDatabaseCount('accounting_alerts', 1);
    }

    public function test_partial_request_and_later_invoice_share_the_same_receipt(): void
    {
        $l = app(ReceiptLedger::class);
        $p = $this->doc(20, 4000, '2026-10-10');
        $p['type'] = 'proforma';
        $p['number'] = 'DEM-test';
        $p['payments'] = [['payment_key' => 'stripe:pi_test', 'paid_at' => '2026-10-10', 'amount_minor' => 4000, 'method' => 'Stripe', 'reference' => 'pi_test']];
        $l->external([$p]);
        $p['type'] = 'invoice';
        $p['number'] = 'FAC-new';
        $p['payment_request_reference'] = 'DEM-test';
        $p['paid_minor'] = 10000;
        $p['paid_date'] = '2026-11-03';
        $p['payments'][] = ['payment_key' => 'stripe:pi_test2', 'paid_at' => '2026-11-03', 'amount_minor' => 6000, 'method' => 'Stripe', 'reference' => 'pi_test2'];
        $l->external([$p]);
        $this->assertDatabaseCount('receipt_entries', 2);
        $this->assertDatabaseCount('accounting_alerts', 0);
        $this->assertSame(4000, $l->period('2026-10')['base_minor']);
        $this->assertSame(6000, $l->period('2026-11')['base_minor']);
    }

    public function test_native_invoice_is_excluded_until_actual_payment_and_fee_does_not_reduce_base(): void
    {
        $c = Client::create(['last_name' => 'Test']);
        $i = Invoice::create(['client_id' => $c->id, 'number' => 'F-test', 'status' => 'emise', 'type' => 'facture', 'amount_due' => 400, 'total_ttc' => 400, 'company_snapshot' => ['siret' => '82503053900035']]);
        $l = app(ReceiptLedger::class);
        $l->sync();
        $this->assertDatabaseCount('receipt_entries', 0);
        Payment::create(['invoice_id' => $i->id, 'amount' => 100, 'paid_at' => '2026-10-05', 'method' => 'virement']);
        $l->sync();
        $l->sync();
        $this->assertDatabaseCount('receipt_entries', 1);
        $this->assertSame(10000, $l->period('2026-10')['base_minor']);
        $r = ReceiptEntry::first();
        $this->expectException(\LogicException::class);
        $r->update(['gross_minor' => 1]);
    }

    public function test_manual_entry_is_deduplicated_and_unknown_category_blocks_archiving(): void
    {
        $this->actingAs(User::factory()->create());
        $this->stripe();
        $l = app(ReceiptLedger::class);
        $row = ['date' => '2026-10-01', 'customer' => 'Autre client', 'document' => 'EXT-1', 'amount' => 250, 'method' => 'Virement', 'category' => ''];
        $l->manual($row, 'same-token');
        $l->manual($row, 'same-token');
        $this->assertDatabaseCount('receipt_entries', 1);
        $this->get('/admin/accounting')->assertOk()->assertSee('Comptabilité');
        $page = Livewire::test(Accounting::class)->set('month', '2026-10')->set('otherReceiptsComplete', true)->call('archivePreparation');
        $this->assertDatabaseCount('accounting_preparations', 0);
        $page->set('classifications.'.ReceiptEntry::first()->id, 'bnc')->set('otherReceiptsComplete', true)->call('archivePreparation');
        $this->assertDatabaseCount('accounting_preparations', 1);
        $snapshot = json_decode(DB::table('accounting_preparations')->value('snapshot'), true);
        $this->assertSame(25000, $snapshot['base_minor']);
        $this->assertSame('prepared_not_filed', $snapshot['status']);
        auth()->logout();
        $this->get('/admin/accounting')->assertRedirect('/admin/login');
    }
}
