<?php

namespace Tests\Feature;

use App\Filament\Pages\BillingOverview;
use App\Models\ExternalInvoice;
use App\Models\User;
use App\Services\Billing\PermisInvoiceImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PermisBillingBridgeTest extends TestCase
{
    use RefreshDatabase;

    private function batch(): array
    {
        return ['schema' => 1, 'source' => 'permis-construire.com', 'exported_at' => now()->toIso8601String(), 'invoices' => [
            ['id' => 7, 'number' => 'FAC-2026-0647', 'client_name' => 'Client exemple', 'issue_date' => '2026-08-10',
                'currency' => 'EUR', 'total_minor' => 128700, 'paid_minor' => 128700, 'vat_minor' => 0, 'status' => 'paid',
                'type' => 'invoice', 'deleted' => false, 'issuer' => ['company_siret' => '82503053900035', 'company_name' => 'Michael DELPECH EI']],
        ]];
    }

    public function test_repeated_import_never_issues_or_sends_and_keeps_legacy_paid_amount(): void
    {
        Mail::fake();
        Notification::fake();
        $service = new PermisInvoiceImporter;
        $batch = $this->batch();
        $this->assertSame(1, $service->import($batch));
        $this->assertSame(0, $service->import($batch));
        $this->assertDatabaseCount('external_invoices', 1);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(128700, ExternalInvoice::first()->payload['paid_minor']);
        Mail::assertNothingSent();
        Notification::assertNothingSent();
    }

    public function test_stale_export_cannot_revert_payment(): void
    {
        $service = new PermisInvoiceImporter;
        $batch = $this->batch();
        $service->import($batch);
        $batch['exported_at'] = now()->subHour()->toIso8601String();
        $batch['invoices'][0]['paid_minor'] = 0;
        $service->import($batch);
        $this->assertSame(128700, ExternalInvoice::first()->payload['paid_minor']);
    }

    public function test_invalid_batch_is_rejected_atomically(): void
    {
        $batch = $this->batch();
        $second = $batch['invoices'][0];
        $second['id'] = 8;
        $second['number'] = 'FAC-2026-0648';
        $second['vat_minor'] = 999999;
        $batch['invoices'][] = $second;
        try {
            (new PermisInvoiceImporter)->import($batch);
            $this->fail('Import should fail');
        } catch (\InvalidArgumentException $e) {
            $this->assertDatabaseCount('external_invoices', 0);
        }
    }

    public function test_overview_is_private_and_renders_imported_invoice(): void
    {
        (new PermisInvoiceImporter)->import($this->batch());
        $this->get('/admin/billing-overview')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin/billing-overview')->assertOk()
            ->assertSee('FAC-2026-0647')->assertSee('Client exemple')->assertSee('82503053900035');
    }

    public function test_fees_and_stripe_balance_are_imported_without_double_counting(): void
    {
        $batch = $this->batch();
        $batch['invoices'][0]['stripe_fees'] = ['status' => 'verified', 'fee_minor' => 1956, 'net_minor' => 126744];
        $batch['stripe_account'] = ['id' => 'acct_test', 'checked_at' => now()->toIso8601String(),
            'available' => [['currency' => 'EUR', 'amount_minor' => 126739]], 'pending' => [['currency' => 'EUR', 'amount_minor' => 0]],
            'payouts' => [], 'transactions' => [['id' => 'txn_test', 'currency' => 'EUR', 'amount_minor' => 128700, 'fee_minor' => 1956, 'net_minor' => 126744, 'created' => time(), 'type' => 'charge']], 'transactions_complete' => true];
        $batch['stripe_account']['transactions'][] = ['id' => 'txn_radar', 'currency' => 'EUR', 'amount_minor' => -5, 'fee_minor' => 0, 'net_minor' => -5, 'created' => time(), 'type' => 'stripe_fee'];
        $service = new PermisInvoiceImporter;
        $this->assertSame(1, $service->import($batch));
        $this->assertSame(0, $service->import($batch));
        $this->assertDatabaseCount('external_billing_accounts', 1);
        $this->assertDatabaseCount('payments', 0);
        $this->actingAs(User::factory()->create())->get('/admin/billing-overview')->assertOk()
            ->assertSee('19,56')->assertSee('267,44')->assertSee('19,61')->assertSee('267,39')->assertSee('toutes les 5 minutes')->assertDontSee('98341402000013');
    }

    public function test_invalid_stripe_fees_reject_the_entire_batch(): void
    {
        $batch = $this->batch();
        $batch['invoices'][0]['stripe_fees'] = ['status' => 'verified', 'fee_minor' => 1956, 'net_minor' => 128700];
        try {
            (new PermisInvoiceImporter)->import($batch);
            $this->fail('Invalid net accepted');
        } catch (\InvalidArgumentException $e) {
            $this->assertDatabaseCount('external_invoices', 0);
        }
    }

    public function test_csv_export_keeps_amounts_and_neutralizes_spreadsheet_formulas(): void
    {
        $batch = $this->batch();
        $batch['invoices'][0]['client_name'] = '=1+1';
        (new PermisInvoiceImporter)->import($batch);
        $response = (new BillingOverview)->exportInvoices();
        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();
        $this->assertStringContainsString("'=1+1", $csv);
        $this->assertStringContainsString('1287.00', $csv);
        $this->assertStringContainsString('FAC-2026-0647', $csv);
    }
    public function test_cancelled_invoices_and_credits_net_to_zero_and_are_archived(): void
    {
        $batch = $this->batch();
        $original = $batch['invoices'][0];
        $original['id'] = 20; $original['number'] = 'FAC-CANCELLED';
        $original['total_minor'] = 39000; $original['paid_minor'] = 0;
        $original['status'] = 'cancelled'; $original['cancellation_credit_id'] = 21;
        $credit = $original;
        $credit['id'] = 21; $credit['number'] = 'AVR-CANCELLED';
        $credit['type'] = 'credit_note'; $credit['status'] = 'issued';
        $credit['cancellation_credit_id'] = null; $credit['credited_invoice_id'] = 20;
        $request = $original;
        $request['id'] = 22; $request['number'] = 'DEM-EXAMPLE'; $request['type'] = 'proforma';
        $request['status'] = 'sent'; $request['cancellation_credit_id'] = null;
        $batch['invoices'] = [$batch['invoices'][0], $original, $credit, $request];
        (new PermisInvoiceImporter)->import($batch);
        $rows = (new BillingOverview)->invoices();
        $this->assertSame(128700, $rows->where('counted',true)->sum('total'));
        $this->assertCount(1, $rows->where('active',true));
        $this->assertCount(3, $rows->where('active',false));
        $this->actingAs(User::factory()->create())->get('/admin/billing-overview')->assertOk()->assertSee('Historique');
    }
}
