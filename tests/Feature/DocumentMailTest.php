<?php

namespace Tests\Feature;

use App\Enums\DocumentCategory;
use App\Enums\InvoiceStatus;
use App\Enums\QuoteStatus;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Quotes\Pages\EditQuote;
use App\Mail\DocumentMail;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Dossier;
use App\Models\Quote;
use App\Models\User;
use App\Services\DocumentMailer;
use App\Services\InvoiceService;
use App\Support\EmailTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentMailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();
        Notification::fake();
        $this->admin = User::factory()->create(['email' => 'mickael@example.com']);
        CompanySetting::current()->update([
            'company_name' => 'M-GREEN', 'iban' => 'FR76 1234', 'cheque_order' => 'M-GREEN',
            'quote_next_number' => 134, 'invoice_next_number' => 117, 'email' => 'mickael@nyleo.fr',
            'email_signature' => "Mickaël Delpech\n07 85 65 91 65",
        ]);
        config(['mail.from.address' => 'mickael@nyleo.fr']);
    }

    private function makeQuote(): Quote
    {
        $client = Client::create(['first_name' => 'Jean', 'last_name' => 'Martin', 'email' => 'jean@example.com']);
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda', 'procedure' => 'DP', 'nature' => 'veranda']);
        $quote = Quote::create(['client_id' => $client->id, 'dossier_id' => $dossier->id, 'subject' => 'DP véranda']);
        $quote->lines()->create(['label' => 'Déclaration préalable', 'quantity' => 1, 'unit_price' => 400]);
        $quote->recalculate();

        return $quote->refresh();
    }

    public function test_quote_template_follows_mickaels_usual_email(): void
    {
        $t = EmailTemplates::forQuote($this->makeQuote());

        $this->assertSame('Déclaration préalable de travaux - Véranda - Envoi du devis', $t['subject']);
        $this->assertStringStartsWith('Bonjour,', $t['body']);
        $this->assertStringContainsString('le devis D'.now()->format('Ym').'-134', $t['body']);
        $this->assertStringContainsString('lancer la production des plans', $t['body']);
    }

    public function test_send_quote_with_edited_text_pdf_link_and_attachment(): void
    {
        $quote = $this->makeQuote();
        Storage::disk('local')->put('email-attachments/abc.pdf', '%PDF-1.4 test');

        DocumentMailer::sendQuote($quote, [
            'to' => 'jean@example.com', 'cc' => ['marie@example.com'],
            'subject' => 'Mon objet', 'body' => "Bonjour,\n\nTexte modifié.\n- point 1",
            'attach_pdf' => true, 'include_link' => true, 'copy_to_me' => true,
            'attachments' => ['email-attachments/abc.pdf'], 'attachment_names' => ['email-attachments/abc.pdf' => 'Plan cadastral.pdf'],
        ]);

        $quote->refresh();
        $this->assertSame(QuoteStatus::Envoye, $quote->status);
        $this->assertSame('D'.now()->format('Ym').'-134', $quote->number);
        Notification::assertNothingSent();

        Mail::assertSent(DocumentMail::class, function (DocumentMail $m) use ($quote) {
            $names = array_map(fn ($a) => $a->as, $m->attachments());

            return $m->hasTo('jean@example.com') && $m->hasCc('marie@example.com') && $m->hasBcc('mickael@nyleo.fr')
                && $m->downloads[0]['label'] === 'Télécharger le devis (PDF)' && str_contains($m->downloads[0]['url'], '/fichiers/devis/'.$quote->id)
                && $m->hasReplyTo('mickael@nyleo.fr')
                && $m->mailSubject === 'Mon objet'
                && $m->buttonLabel === 'Lire et signer mon devis'
                && in_array("Devis-{$quote->number}.pdf", $names, true)
                && in_array('Plan cadastral.pdf', $names, true);
        });
        Storage::disk('local')->assertMissing('email-attachments/abc.pdf');
        $this->assertDatabaseHas('dossier_events', ['dossier_id' => $quote->dossier_id, 'type' => 'email']);
    }

    public function test_rendered_mail_escapes_html_and_keeps_signature(): void
    {
        $html = (new DocumentMail('Objet', "Bonjour,\n\n<script>x</script>\n- Votre date et lieu de naissance"))->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertMatchesRegularExpression('/<li[^>]*>Votre date et lieu de naissance<\/li>/', $html);
        $this->assertStringContainsString('07 85 65 91 65', $html);
    }

    public function test_send_draft_invoice_issues_it_and_attaches_plans(): void
    {
        $quote = $this->makeQuote();
        $quote->forceFill(['status' => QuoteStatus::Signe])->save();
        $invoice = InvoiceService::createFinalFromQuote($quote);
        Storage::disk('local')->put('dossiers/plan.pdf', '%PDF');
        $plan = $quote->dossier->documents()->create(['category' => DocumentCategory::Plan, 'name' => 'V1 - DP - MARTIN', 'path' => 'dossiers/plan.pdf']);

        $t = EmailTemplates::forInvoice($invoice);
        $this->assertSame('Plans DP Véranda + facture', $t['subject']);
        $this->assertStringContainsString('F'.now()->format('Ym').'-117', $t['body']);
        $this->assertStringContainsString('IBAN FR76 1234', $t['body']);
        $this->assertStringContainsString('date et lieu de naissance', $t['body']);

        DocumentMailer::sendInvoice($invoice, $t + ['to' => 'jean@example.com', 'attach_pdf' => true, 'documents' => [$plan->id]]);

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Emise, $invoice->status);
        $this->assertNotNull($invoice->sent_at);
        Mail::assertSent(DocumentMail::class, function (DocumentMail $m) use ($invoice) {
            $names = array_map(fn ($a) => $a->as, $m->attachments());

            return $names === ["Facture-{$invoice->number}.pdf", 'V1 - DP - MARTIN.pdf']
                && $m->buttonLabel === 'Consulter ma facture'
                && array_column($m->downloads, 'label') === ['Télécharger la facture (PDF)', 'Télécharger « V1 - DP - MARTIN (V1) »'];
        });
    }

    public function test_cerfa_request_disappears_when_birth_info_is_known(): void
    {
        $quote = $this->makeQuote();
        $quote->client->update(['birth_date' => '1960-01-01', 'birth_place' => 'Périgueux']);
        $invoice = InvoiceService::createFinalFromQuote($quote);

        $this->assertStringNotContainsString('naissance', EmailTemplates::forInvoice($invoice)['body']);
    }

    public function test_admin_send_buttons_open_and_send(): void
    {
        $quote = $this->makeQuote();
        $this->actingAs($this->admin);

        Livewire::test(EditQuote::class, ['record' => $quote->getRouteKey()])
            ->mountAction('sendEmail')
            ->assertActionDataSet(['to' => 'jean@example.com', 'subject' => 'Déclaration préalable de travaux - Véranda - Envoi du devis'])
            ->setActionData(['body' => "Bonjour,\n\nTexte à moi."])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        Mail::assertSent(DocumentMail::class, fn ($m) => str_contains($m->body, 'Texte à moi.'));
        $this->assertSame(QuoteStatus::Envoye, $quote->refresh()->status);

        $quote->forceFill(['status' => QuoteStatus::Signe])->save();
        $invoice = InvoiceService::createFinalFromQuote($quote);
        Livewire::test(EditInvoice::class, ['record' => $invoice->getRouteKey()])
            ->callAction('sendEmail')
            ->assertHasNoActionErrors();

        $this->assertSame(InvoiceStatus::Emise, $invoice->refresh()->status);
        Mail::assertSentCount(2);
    }
}
