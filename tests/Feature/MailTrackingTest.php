<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Models\Client;
use App\Models\ClientActivity;
use App\Models\CompanySetting;
use App\Models\Dossier;
use App\Models\EmailMessage;
use App\Models\Quote;
use App\Models\User;
use App\Services\DocumentMailer;
use App\Support\MailTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Suivi des e-mails clients : ouverture, clics, téléchargement du PDF, consultation ; jamais pour Mickaël. */
class MailTrackingTest extends TestCase
{
    use RefreshDatabase;

    private const UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['mail.default' => 'array', 'queue.default' => 'sync', 'mail.from.address' => 'mickael@nyleo.fr']);
        CompanySetting::current()->update(['email' => 'mickael@nyleo.fr', 'quote_next_number' => 643]);
    }

    private function sentMessages()
    {
        return collect(Mail::mailer()->getSymfonyTransport()->messages())->map(fn ($m) => $m->getOriginalMessage());
    }

    private function sendQuote(): Quote
    {
        $client = Client::create(['first_name' => 'Jean', 'last_name' => 'Martin', 'email' => 'jean@example.com']);
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda', 'procedure' => 'DP', 'nature' => 'veranda']);
        $quote = Quote::create(['client_id' => $client->id, 'dossier_id' => $dossier->id, 'subject' => 'DP véranda']);
        $quote->lines()->create(['label' => 'Déclaration préalable', 'quantity' => 1, 'unit_price' => 400]);
        $quote->recalculate();

        DocumentMailer::sendQuote($quote->refresh(), [
            'to' => 'jean@example.com', 'subject' => 'Votre devis', 'body' => "Bonjour,\n\nVoici le devis.",
            'attach_pdf' => true, 'include_link' => true, 'include_downloads' => true,
        ]);

        return $quote->refresh();
    }

    public function test_client_email_has_view_and_pdf_buttons_tracked_links_and_pixel_and_untracked_copy(): void
    {
        $quote = $this->sendQuote();
        $messages = $this->sentMessages();
        $this->assertCount(2, $messages);

        $client = $messages->first(fn ($m) => $m->getTo()[0]->getAddress() === 'jean@example.com');
        $copy = $messages->first(fn ($m) => $m->getTo()[0]->getAddress() === 'mickael@nyleo.fr');
        $this->assertSame([], $client->getBcc());

        $email = EmailMessage::sole();
        $this->assertSame($quote->id, $email->quote_id);
        $this->assertSame($quote->client_id, $email->client_id);
        $this->assertNotNull($email->sent_at);
        $this->assertSame(['Lire et signer mon devis', 'Télécharger le devis (PDF)'], array_column($email->links, 'label'));

        $html = $client->getHtmlBody();
        $this->assertStringContainsString(route('mail.open', $email->token), $html);
        $this->assertStringContainsString(e(route('mail.click', [$email->token, 1])), $html);
        $this->assertStringNotContainsString('href="'.e($email->links[1]['url']).'"', $html);

        // La copie de Mickaël n'a ni pixel ni lien suivi, et ses liens portent ?copie=1.
        $this->assertStringNotContainsString('/m/', $copy->getHtmlBody());
        $this->assertStringContainsString('copie=1', $copy->getHtmlBody());
    }

    public function test_open_click_download_and_portal_view_are_recorded_with_date(): void
    {
        $quote = $this->sendQuote();
        $email = EmailMessage::sole();

        $this->withHeader('User-Agent', self::UA)->get(route('mail.open', $email->token))
            ->assertOk()->assertHeader('Content-Type', 'image/gif');
        // Rechargement immédiat du pixel : une seule ouverture.
        $this->withHeader('User-Agent', self::UA)->get(route('mail.open', $email->token));

        $pdf = $this->withHeader('User-Agent', self::UA)->get(route('mail.click', [$email->token, 1]));
        $pdf->assertRedirect($email->links[1]['url']);
        $this->withHeader('User-Agent', self::UA)->get($email->links[1]['url'])->assertOk();

        $email->refresh();
        $this->assertSame(1, $email->open_count);
        $this->assertNotNull($email->opened_at);
        $this->assertSame(1, $email->click_count);

        $types = ClientActivity::orderBy('id')->pluck('type')->all();
        $this->assertSame([ClientActivity::OPENED, ClientActivity::CLICKED, ClientActivity::DOWNLOADED], $types);
        $this->assertSame("PDF « Devis-{$quote->number}.pdf » téléchargé", ClientActivity::latest('id')->first()->label);
        $this->assertDatabaseHas('dossier_events', ['dossier_id' => $quote->dossier_id, 'type' => 'suivi_client']);

        // Consultation dans l'espace client.
        $this->actingAs($quote->client, 'client')->withHeader('User-Agent', self::UA)
            ->get(route('portal.quotes.show', $quote))->assertOk();
        $this->assertSame(ClientActivity::VIEWED, ClientActivity::latest('id')->first()->type);
        $this->assertNotNull($quote->refresh()->viewed_at);
    }

    public function test_nothing_is_recorded_for_mickael_bots_or_his_copy(): void
    {
        $quote = $this->sendQuote();
        $email = EmailMessage::sole();

        // Robot d'antivirus.
        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Barracuda Sentinel scanner)')->get(route('mail.open', $email->token));
        // Lien de la copie de Mickaël.
        $this->withHeader('User-Agent', self::UA)->get($email->links[1]['url'].'&copie=1')->assertOk();
        // Mickaël connecté à l'administration.
        $this->actingAs(User::factory()->create(), 'web')->withHeader('User-Agent', self::UA)->get(route('mail.open', $email->token));

        $this->assertSame(0, ClientActivity::count());
        $this->assertSame(0, $email->refresh()->open_count);
    }

    public function test_download_link_requires_a_valid_signature(): void
    {
        $quote = $this->sendQuote();
        $this->get('/fichiers/devis/'.$quote->id)->assertForbidden();

        $draft = Quote::create(['client_id' => $quote->client_id, 'status' => QuoteStatus::Brouillon]);
        $this->get(MailTracking::downloadUrl($draft))->assertNotFound();
    }

    public function test_magic_link_from_copy_flags_the_session(): void
    {
        $quote = $this->sendQuote();
        $url = EmailMessage::sole()->links[0]['url'];

        $this->withHeader('User-Agent', self::UA)->get($url.'&copie=1')->assertRedirect();
        $this->withHeader('User-Agent', self::UA)->get(route('portal.quotes.show', $quote))->assertOk();

        $this->assertSame(0, ClientActivity::count());
        $this->assertNull($quote->refresh()->viewed_at);
    }

    public function test_admin_shows_tracking_on_quote_list_quote_dossier_and_client(): void
    {
        $quote = $this->sendQuote();
        $email = EmailMessage::sole();
        $this->withHeader('User-Agent', self::UA)->get(route('mail.click', [$email->token, 1]));
        $this->withHeader('User-Agent', self::UA)->get($email->links[1]['url']);
        $when = ClientActivity::when(ClientActivity::latest('id')->first()->created_at);

        $this->actingAs(User::factory()->create(), 'web');
        $plain = fn ($response) => str_replace(["\u{00A0}", "\u{202F}", '&nbsp;'], ' ', $response->assertOk()->getContent());
        $when = str_replace("\u{00A0}", ' ', $when);
        $this->assertStringContainsString('Vu le '.$when, $plain($this->get('/admin/quotes')));
        $page = $plain($this->get("/admin/quotes/{$quote->id}/edit"));
        $this->assertStringContainsString('Suivi client', $page);
        $this->assertStringContainsString("PDF « Devis-{$quote->number}.pdf » téléchargé", $page);
        file_put_contents('/tmp/claude-0/dossier.html', $this->get("/admin/dossiers/{$quote->dossier_id}")->getContent());
        $this->get("/admin/dossiers/{$quote->dossier_id}")->assertOk()->assertSee('Suivi client');
        $this->get("/admin/clients/{$quote->client_id}/edit")->assertOk()->assertSee('Suivi client');
        $this->get('/admin/invoices')->assertOk();
    }
}
