<?php

namespace Tests\Feature;

use App\Mail\DocumentMail;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Dossier;
use App\Models\Invoice;
use App\Models\Quote;
use App\Notifications\InvoiceSent;
use App\Services\CardPayment;
use App\Services\DocumentMailer;
use App\Services\InvoiceService;
use App\Support\EmailTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CardPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        config(['services.stripe.secret' => 'sk_test_123', 'services.stripe.webhook_secret' => 'whsec_test']);
        CompanySetting::current()->update(['company_name' => 'M-GREEN', 'iban' => 'FR14 2004 1010 0505 0001 3M02 606', 'cheque_order' => 'M-GREEN']);
    }

    private function invoice(bool $card = true, bool $issue = true): Invoice
    {
        $client = Client::create(['first_name' => 'Jean', 'last_name' => 'Martin', 'email' => 'jean'.uniqid().'@example.com']);
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda', 'procedure' => 'DP']);
        $quote = Quote::create(['client_id' => $client->id, 'dossier_id' => $dossier->id, 'subject' => 'DP véranda']);
        $quote->lines()->create(['label' => 'Déclaration préalable', 'quantity' => 1, 'unit_price' => 450.5]);
        $quote->recalculate();
        $invoice = InvoiceService::createFinalFromQuote($quote->refresh());
        $invoice->update(['card_payment' => $card]);

        return $issue ? InvoiceService::issue($invoice, notify: false)->refresh() : $invoice->refresh();
    }

    private function stripeSession(Invoice $invoice, array $over = []): array
    {
        return $over + [
            'id' => 'cs_test_abc',
            'object' => 'checkout.session',
            'payment_status' => 'paid',
            'payment_intent' => 'pi_test_123',
            'amount_total' => (int) round($invoice->remaining() * 100),
            'currency' => 'eur',
            'client_reference_id' => (string) $invoice->id,
            'metadata' => ['invoice_id' => (string) $invoice->id],
        ];
    }

    public function test_card_payment_needs_the_option_and_the_stripe_key(): void
    {
        $invoice = $this->invoice();
        $this->assertTrue(CardPayment::available($invoice));
        $this->assertNotNull(CardPayment::url($invoice));

        $this->assertFalse(CardPayment::available($this->invoice(card: false)));
        $this->assertFalse(CardPayment::available($this->invoice(issue: false)));

        config(['services.stripe.secret' => null]);
        $this->assertFalse(CardPayment::available($invoice));
        $this->assertNull(CardPayment::url($invoice));
    }

    public function test_pay_link_redirects_to_stripe_checkout_for_the_remaining_amount(): void
    {
        $invoice = $this->invoice();
        InvoiceService::addPayment($invoice, 100, 'cheque', now(), notify: false);
        Http::fake(['api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test_abc', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_abc'])]);

        $this->get(CardPayment::url($invoice->refresh()))->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_abc');

        Http::assertSent(function (HttpRequest $r) use ($invoice) {
            return $r->hasHeader('Authorization', 'Bearer sk_test_123')
                && $r['line_items'][0]['price_data']['unit_amount'] === 35050
                && $r['metadata']['invoice_id'] == $invoice->id
                && $r['customer_email'] === $invoice->client->email
                && str_contains($r['success_url'], '{CHECKOUT_SESSION_ID}');
        });
    }

    public function test_pay_link_must_be_signed_and_shows_a_page_when_unavailable(): void
    {
        $invoice = $this->invoice(card: false);
        $this->get(route('payment.card', $invoice))->assertForbidden();
        $this->get(URL::temporarySignedRoute('payment.card', now()->addDay(), ['invoice' => $invoice->id]))
            ->assertOk()->assertSee('Paiement par carte indisponible');
    }

    public function test_success_page_records_the_payment_once(): void
    {
        $invoice = $this->invoice();
        Http::fake(['api.stripe.com/v1/checkout/sessions/cs_test_abc' => Http::response($this->stripeSession($invoice))]);
        $url = URL::temporarySignedRoute('payment.card.success', now()->addDay(), ['invoice' => $invoice->id]).'&session_id=cs_test_abc';

        $this->get($url)->assertOk()->assertSee('votre paiement est bien reçu');
        $this->get($url)->assertOk();

        $invoice->refresh();
        $this->assertSame('payee', $invoice->status->value);
        $this->assertCount(1, $invoice->payments);
        $this->assertSame('carte', $invoice->payments->first()->method);
        $this->assertSame('pi_test_123', $invoice->payments->first()->reference);
        $this->assertFalse(CardPayment::available($invoice));
    }

    public function test_success_page_needs_a_signature(): void
    {
        $invoice = $this->invoice();
        $this->get(route('payment.card.success', $invoice).'?session_id=cs_test_abc')->assertForbidden();
    }

    public function test_success_page_ignores_a_session_of_another_invoice(): void
    {
        $invoice = $this->invoice();
        $other = $this->invoice();
        Http::fake(['api.stripe.com/*' => Http::response($this->stripeSession($other))]);
        $url = URL::temporarySignedRoute('payment.card.success', now()->addDay(), ['invoice' => $invoice->id]).'&session_id=cs_test_abc';

        $this->get($url)->assertOk()->assertSee('en cours de vérification');
        $this->assertSame(0, $invoice->payments()->count());
    }

    public function test_webhook_records_the_payment_with_a_valid_signature_only(): void
    {
        $invoice = $this->invoice();
        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => $this->stripeSession($invoice)]]);
        $t = time();

        $this->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => "t={$t},v1=bad", 'CONTENT_TYPE' => 'application/json'], $payload)->assertStatus(400);
        $this->assertSame(0, $invoice->payments()->count());

        $sig = hash_hmac('sha256', $t.'.'.$payload, 'whsec_test');
        $headers = ['HTTP_STRIPE_SIGNATURE' => "t={$t},v1={$sig}", 'CONTENT_TYPE' => 'application/json'];
        $this->call('POST', '/stripe/webhook', [], [], [], $headers, $payload)->assertOk();
        $this->call('POST', '/stripe/webhook', [], [], [], $headers, $payload)->assertOk();

        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame('payee', $invoice->refresh()->status->value);
    }

    public function test_unpaid_session_is_not_recorded(): void
    {
        $invoice = $this->invoice();
        $this->assertNull(CardPayment::record($this->stripeSession($invoice, ['payment_status' => 'unpaid'])));
        $this->assertSame(0, $invoice->payments()->count());
    }

    public function test_portal_shows_the_card_button_only_when_enabled(): void
    {
        $invoice = $this->invoice();
        $this->actingAs($invoice->client, 'client')->get(route('portal.invoices.show', $invoice))
            ->assertOk()->assertSee('Payer ma facture par carte bancaire')->assertSee('/paiement/facture/'.$invoice->id, false);

        $invoice->update(['card_payment' => false]);
        $this->actingAs($invoice->client, 'client')->get(route('portal.invoices.show', $invoice))
            ->assertOk()->assertDontSee('/paiement/facture/'.$invoice->id, false);
    }

    public function test_invoice_email_mentions_the_card_without_a_pay_button(): void
    {
        $invoice = $this->invoice();
        $html = (string) (new InvoiceSent($invoice))->toMail($invoice->client)->render();

        $this->assertStringContainsString('Par carte bancaire', $html);
        $this->assertStringNotContainsString('/paiement/facture/', $html);

        $invoice->update(['card_payment' => false]);
        $html = (string) (new InvoiceSent($invoice->refresh()))->toMail($invoice->client)->render();
        $this->assertStringNotContainsString('Par carte bancaire', $html);
    }

    public function test_one_click_email_has_the_payment_line_but_no_pay_button(): void
    {
        Mail::fake();
        $invoice = $this->invoice(issue: false);
        $template = EmailTemplates::forInvoice($invoice);
        $this->assertStringContainsString('par carte bancaire', $template['body']);

        DocumentMailer::sendInvoice($invoice, ['to' => $invoice->client->email] + $template);

        Mail::assertSent(DocumentMail::class, fn (DocumentMail $m) => $m->payUrl === null && str_contains($m->body, 'par carte bancaire'));
    }
}
