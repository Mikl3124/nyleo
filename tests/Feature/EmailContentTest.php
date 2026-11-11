<?php

namespace Tests\Feature;

use App\Mail\DocumentMail;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Dossier;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\AdminAlert;
use App\Notifications\InvoiceSent;
use App\Notifications\PortalLink;
use App\Notifications\QuoteSent;
use App\Services\InvoiceService;
use App\Services\QuoteService;
use Filament\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmailContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        CompanySetting::current()->update([
            'company_name' => 'M-GREEN', 'email' => 'mickael@example.com', 'phone' => '07 85 65 91 65',
            'iban' => 'FR14 2004 1010 0505 0001 3M02 606', 'cheque_order' => 'M-GREEN',
        ]);
    }

    private function quote(): Quote
    {
        $client = Client::create(['civility' => 'Madame', 'first_name' => 'Anne', 'last_name' => 'Durand', 'email' => 'anne@example.com']);
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda Durand', 'procedure' => 'DP']);
        $quote = Quote::create(['client_id' => $client->id, 'dossier_id' => $dossier->id, 'subject' => 'DP véranda']);
        $quote->lines()->create(['label' => 'Déclaration préalable', 'quantity' => 1, 'unit_price' => 450]);
        $quote->recalculate();
        QuoteService::send($quote, notify: false);

        return $quote->refresh();
    }

    public function test_client_emails_have_a_title_a_summary_an_inbox_preview_and_a_reply_address(): void
    {
        $quote = $this->quote();
        $mail = (new QuoteSent($quote))->toMail($quote->client);
        $html = (string) $mail->render();

        $this->assertSame([['mickael@example.com', null]], $mail->replyTo);
        $this->assertMatchesRegularExpression('#<h1[^>]*>Votre devis est prêt</h1>#u', $html);
        $this->assertStringContainsString('Bonjour Madame Durand,', $html);
        $this->assertStringContainsString('class="recap"', $html);
        $this->assertStringContainsString('450,00', $html);
        $this->assertStringContainsString('class="preheader"', $html);
        $this->assertStringContainsString('à signer en ligne', $html);
        // Bouton lisible dans Outlook : couleur aussi posée en attribut.
        $this->assertStringContainsString('bgcolor="#e8a92b"', $html);
        $this->assertStringContainsString('Lire et signer mon devis', $html);
    }

    public function test_greeting_without_civility_uses_the_full_name(): void
    {
        $client = Client::create(['first_name' => 'Jean', 'last_name' => 'Martin', 'email' => 'j@example.com']);
        $html = (string) (new PortalLink)->toMail($client)->render();

        $this->assertStringContainsString('Bonjour Jean Martin,', $html);
        $this->assertStringContainsString('Ouvrir mon espace client', $html);
        $this->assertStringContainsString('aucun mot de passe', $html);
    }

    public function test_invoice_email_shows_how_to_pay(): void
    {
        $quote = $this->quote();
        $invoice = InvoiceService::issue(InvoiceService::createFinalFromQuote($quote), notify: false)->refresh();
        $html = (string) (new InvoiceSent($invoice))->toMail($quote->client)->render();

        $this->assertStringContainsString('Comment régler ?', $html);
        $this->assertStringContainsString('FR14 2004 1010 0505 0001 3M02 606', $html);
        $this->assertStringNotContainsString('chèque', $html);
        $this->assertStringContainsString('/espace/factures/'.$invoice->id, urldecode($html));
        $this->assertStringContainsString('dépose votre dossier en mairie', $html);
    }

    public function test_admin_alert_uses_the_blue_admin_button(): void
    {
        $user = User::create(['name' => 'M', 'email' => 'a@example.com', 'password' => 'x']);
        $html = (string) (new AdminAlert('Nouvelle demande de devis', 'Jean Martin — Véranda', 'https://nyleo.fr/admin'))->toMail($user)->render();

        $this->assertMatchesRegularExpression('#<h1[^>]*>Nouvelle demande de devis</h1>#u', $html);
        $this->assertStringContainsString('bgcolor="#1d3f91"', $html);
        $this->assertStringNotContainsString('Bonjour', $html);
    }

    public function test_admin_password_reset_email_is_in_french(): void
    {
        $user = User::create(['name' => 'M', 'email' => 'a@example.com', 'password' => 'x']);
        $n = new ResetPassword('tok');
        $n->url = 'https://nyleo.fr/admin/password-reset/reset?token=tok';
        $mail = $n->toMail($user);
        $html = (string) $mail->render();

        $this->assertSame('Choisir un nouveau mot de passe Nyleo', $mail->subject);
        $this->assertStringContainsString('Choisir un nouveau mot de passe', $html);
        $this->assertStringNotContainsString('Reset Password', $html);
    }

    public function test_one_click_email_gets_an_inbox_preview_from_its_first_paragraph(): void
    {
        $this->assertSame('Veuillez trouver ci-joint le devis.', DocumentMail::preheader("Bonjour,\n\nVeuillez trouver ci-joint le devis.\n\nCordialement"));
        $this->assertNull(DocumentMail::preheader('Bonjour,'));

        $html = (new DocumentMail('Objet', "Bonjour,\n\nVoici les plans.", [], 'Voir mon espace client', 'https://nyleo.fr/x'))->render();
        $this->assertStringContainsString('Voici les plans.', $html);
        $this->assertStringContainsString('Si le bouton « Voir mon espace client » ne fonctionne pas', $html);
    }
}
