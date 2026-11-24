<?php

namespace Tests\Feature;

use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Mail\DocumentMail;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Dossier;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\User;
use App\Services\DocumentMailer;
use App\Services\InvoiceService;
use App\Support\EmailTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceSendOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Mail::fake();
        config(['services.stripe.secret' => 'sk_test_123']);
        CompanySetting::current()->update(['iban' => 'FR14 2004 1010 0505 0001 3M02 606', 'cheque_order' => 'M-GREEN']);
    }

    private function draftInvoice(): Invoice
    {
        $client = Client::create(['first_name' => 'Jean', 'last_name' => 'Martin', 'email' => 'jean@example.com']);
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Projet de Jean Martin', 'procedure' => 'DP']);
        $quote = Quote::create(['client_id' => $client->id, 'dossier_id' => $dossier->id, 'subject' => 'DP véranda']);
        $quote->lines()->create(['label' => 'DÉCLARATION PRÉALABLE – VÉRANDA : constitution et dépôt du dossier', 'quantity' => 1, 'unit_price' => 400]);
        $quote->recalculate();

        return InvoiceService::createFinalFromQuote($quote->refresh())->refresh();
    }

    public function test_send_window_has_card_payment_checked_and_pdf_download_buttons_off(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = $this->draftInvoice();

        Livewire::test(EditInvoice::class, ['record' => $invoice->getRouteKey()])
            ->mountAction('sendEmail')
            ->assertSchemaStateSet(['card_payment' => true, 'include_downloads' => false, 'subject' => 'Plans DP Véranda + facture'], 'mountedActionSchema0');
    }

    public function test_subject_guesses_the_kind_of_works(): void
    {
        $this->assertSame('Pergola', EmailTemplates::nature(null, ['DP pergola bioclimatique']));
        $this->assertSame('Garage', EmailTemplates::nature(null, ['Permis de construire – garage double']));
        $this->assertSame('Maison', EmailTemplates::nature(null, ['PC maison individuelle']));
        $this->assertSame('', EmailTemplates::nature(null, ['Déclaration préalable']));
    }

    public function test_card_choice_is_saved_on_the_invoice_and_adjusts_the_text(): void
    {
        $invoice = $this->draftInvoice();
        $template = EmailTemplates::forInvoice($invoice, card: true);
        $this->assertStringContainsString(EmailTemplates::CARD_LINE, $template['body']);

        DocumentMailer::sendInvoice($invoice, ['to' => 'jean@example.com', 'card_payment' => false] + $template);
        $this->assertFalse((bool) $invoice->refresh()->card_payment);
        Mail::assertSent(DocumentMail::class, fn (DocumentMail $m) => $m->payUrl === null && ! str_contains($m->body, 'par carte'));

        DocumentMailer::sendInvoice($invoice, ['to' => 'jean@example.com', 'card_payment' => true] + EmailTemplates::forInvoice($invoice, card: false));
        $this->assertTrue((bool) $invoice->refresh()->card_payment);
        Mail::assertSent(DocumentMail::class, fn (DocumentMail $m) => $m->payUrl === null && str_contains($m->body, 'par carte'));
    }
}
