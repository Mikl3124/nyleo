<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Dossier;
use App\Models\Invoice;
use App\Models\Quote;
use App\Services\InvoiceService;
use App\Services\PdfService;
use App\Support\PaymentQr;
use chillerlan\QRCode\QRCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentQrTest extends TestCase
{
    use RefreshDatabase;

    private const IBAN = 'FR14 2004 1010 0505 0001 3M02 606';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        CompanySetting::current()->update(['company_name' => 'M-GREEN', 'iban' => self::IBAN, 'bic' => 'PSST FRPP XXX', 'cheque_order' => 'M-GREEN']);
    }

    private function issuedInvoice(): Invoice
    {
        $client = Client::create(['first_name' => 'Jean', 'last_name' => 'Martin', 'email' => 'jean@example.com']);
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda', 'procedure' => 'DP']);
        $quote = Quote::create(['client_id' => $client->id, 'dossier_id' => $dossier->id, 'subject' => 'DP véranda']);
        $quote->lines()->create(['label' => 'Déclaration préalable', 'quantity' => 1, 'unit_price' => 450.5]);
        $quote->recalculate();
        $invoice = InvoiceService::createFinalFromQuote($quote->refresh());

        return InvoiceService::issue($invoice, notify: false)->refresh();
    }

    public function test_payload_follows_the_epc_format(): void
    {
        $invoice = $this->issuedInvoice();
        $payload = PaymentQr::payload($invoice, CompanySetting::current()->snapshot());

        $this->assertSame(implode("\n", [
            'BCD', '002', '1', 'SCT', 'PSSTFRPPXXX', 'M-GREEN', 'FR1420041010050500013M02606', 'EUR450.50', '', '', $invoice->number,
        ]), $payload);
    }

    public function test_payload_uses_the_remaining_amount(): void
    {
        $invoice = $this->issuedInvoice();
        InvoiceService::addPayment($invoice, 200, 'cheque', now(), notify: false);

        $this->assertStringContainsString("\nEUR250.50\n", PaymentQr::payload($invoice->refresh(), CompanySetting::current()->snapshot()));
    }

    public function test_no_qr_code_without_valid_iban_for_drafts_paid_invoices_or_credit_notes(): void
    {
        $invoice = $this->issuedInvoice();
        $company = CompanySetting::current()->snapshot();

        $this->assertNull(PaymentQr::payload($invoice, ['iban' => null] + $company));
        $this->assertNull(PaymentQr::payload($invoice, ['iban' => 'FR15 2004 1010 0505 0001 3M02 606'] + $company));
        $this->assertNull(PaymentQr::payload(new Invoice(['type' => 'facture', 'amount_due' => 100]), $company));
        $credit = InvoiceService::createCreditNote($invoice, null, 'Erreur');
        $this->assertNull(PaymentQr::payload(InvoiceService::issue($credit, notify: false)->refresh(), $company));

        InvoiceService::addPayment($invoice->refresh(), 450.5, 'virement', now(), notify: false);
        $this->assertNull(PaymentQr::payload($invoice->refresh(), $company));
    }

    public function test_generated_image_decodes_to_the_payload(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD absent');
        }
        $invoice = $this->issuedInvoice();
        $company = CompanySetting::current()->snapshot();
        $png = PaymentQr::png($invoice, $company);
        $this->assertStringStartsWith('data:image/png;base64,', $png);

        $decoded = (new QRCode)->readFromBlob(base64_decode(substr($png, strlen('data:image/png;base64,'))));
        $this->assertSame(PaymentQr::payload($invoice, $company), (string) $decoded);
    }

    public function test_invoice_pdf_and_client_space_show_the_qr_code(): void
    {
        $invoice = $this->issuedInvoice();
        $company = $invoice->company_snapshot;

        $html = view('pdf.invoice', ['invoice' => $invoice->load('lines', 'payments'), 'company' => $company, 'client' => $invoice->client_snapshot])->render();
        $this->assertStringContainsString('alt="QR code de virement"', $html);
        $this->assertStringContainsString("Scannez avec l'application de votre banque", $html);
        $this->assertNotEmpty(PdfService::invoice($invoice)->output());

        $this->actingAs($invoice->client, 'client')
            ->get(route('portal.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Payer ma facture')
            ->assertSee('data:image/svg+xml;base64,', false)
            ->assertSee("Scannez avec l'application de votre banque", false)
            ->assertSee('FR14 2004 1010 0505 0001 3M02 606');
    }

    public function test_no_qr_code_in_client_space_without_iban(): void
    {
        $invoice = $this->issuedInvoice();
        CompanySetting::current()->update(['iban' => null]);

        $this->actingAs($invoice->client, 'client')
            ->get(route('portal.invoices.show', $invoice))
            ->assertOk()
            ->assertDontSee('data:image/svg+xml;base64,', false)
            ->assertDontSee('Scannez avec');
    }

    public function test_invoice_page_is_private_and_offers_no_cheque(): void
    {
        $invoice = $this->issuedInvoice();
        $other = Client::create(['first_name' => 'Paul', 'last_name' => 'Autre', 'email' => 'paul@example.com']);

        $this->actingAs($other, 'client')->get(route('portal.invoices.show', $invoice))->assertNotFound();
        $this->actingAs($invoice->client, 'client')->get(route('portal.invoices.show', $invoice))
            ->assertOk()->assertSee('Payer ma facture')->assertSee($invoice->number)->assertDontSee('chèque');
        $this->actingAs($invoice->client, 'client')->get(route('portal.dossiers.show', $invoice->dossier))
            ->assertOk()->assertSee('Voir et payer ma facture')->assertDontSee('compléter vos informations');
    }
}
