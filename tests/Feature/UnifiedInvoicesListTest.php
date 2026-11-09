<?php

namespace Tests\Feature;

use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Models\Client;
use App\Models\ExternalInvoice;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\ExternalInvoiceCatalog;
use App\Services\Billing\PermisInvoiceImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class UnifiedInvoicesListTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_section_shows_both_sources_without_creating_native_copies(): void
    {
        Mail::fake();
        Notification::fake();
        $this->actingAs(User::factory()->create());
        $client = Client::create(['last_name' => 'Lévêque', 'first_name' => 'Nathalie', 'email' => 'leveque@example.test']);
        Invoice::create(['client_id' => $client->id, 'number' => 'F202611-643', 'status' => 'emise', 'type' => 'facture', 'issue_date' => '2026-11-08', 'total_ttc' => 400, 'amount_due' => 400]);
        $batch = ['schema' => 1, 'source' => 'permis-construire.com', 'exported_at' => now()->toIso8601String(), 'invoices' => []];
        foreach ([[7, 'Leroy', 128700], [8, 'Chatelier', 47500], [10, 'TAILPIED', 62500]] as [$id,$name,$total]) {
            $batch['invoices'][] = ['id' => $id, 'number' => 'FAC-2026-'.(640 + $id), 'client_name' => $name, 'issue_date' => '2026-10-24', 'currency' => 'EUR', 'total_minor' => $total, 'paid_minor' => $total, 'vat_minor' => 0, 'status' => 'paid', 'type' => 'invoice', 'deleted' => false, 'issuer' => ['company_name' => 'Michaël DELPECH EI', 'company_siret' => '82503053900035']];
        }
        $importer = new PermisInvoiceImporter;
        $importer->import($batch);
        $importer->import($batch);
        $this->get('/admin/invoices')->assertOk()->assertSee('Lévêque')->assertSee('TAILPIED')->assertSee('Chatelier')->assertSee('Leroy')->assertSee('FAC-2026-647')->assertSee('F202611-643');
        $this->assertSame(1, substr_count($this->get('/admin/invoices')->getContent(), '<table'));
        Livewire::test(ListInvoices::class)->searchTable('leveque')->assertSee('Lévêque')->assertDontSee('TAILPIED');
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('external_invoices', 3);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(3, ExternalInvoiceCatalog::issuedInvoices()->count());
        Livewire::test(ListInvoices::class)->searchTable('TAILPIED')->assertSee('TAILPIED')->assertDontSee('Leroy')->assertDontSee('Lévêque');
        $this->assertSame('https://permis-construire.com/admin/invoices/10/pdf', ExternalInvoiceCatalog::url(ExternalInvoice::where('source_id', 10)->first(), true));
        Mail::assertNothingSent();
        Notification::assertNothingSent();
    }

    public function test_payment_requests_and_cancelled_archived_or_credit_documents_are_not_shown(): void
    {
        foreach (['proforma', 'credit_note', 'cancelled', 'deleted', 'unpaid'] as $i => $kind) {
            $payload = ['issuer' => ['company_siret' => '82503053900035'], 'type' => in_array($kind, ['proforma', 'credit_note']) ? $kind : 'invoice', 'status' => $kind === 'cancelled' ? 'cancelled' : ($kind === 'unpaid' ? 'sent' : 'paid'), 'deleted' => $kind === 'deleted'];
            ExternalInvoice::create(['source' => 'permis-construire.com', 'source_id' => $i + 1, 'number' => 'TEST-'.$i, 'payload' => $payload, 'content_hash' => str_repeat('a', 64), 'source_exported_at' => now()->toIso8601String(), 'synced_at' => now()]);
        }
        $this->assertSame(0, ExternalInvoiceCatalog::issuedInvoices()->count());
        auth()->logout();
        $this->get('/admin/invoices')->assertRedirect('/admin/login');
    }
}
