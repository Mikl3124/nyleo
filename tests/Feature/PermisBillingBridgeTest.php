<?php

namespace Tests\Feature;

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
}
