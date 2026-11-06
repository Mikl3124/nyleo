<?php

namespace Tests\Feature;

use App\Mail\DocumentMail;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\AdminAlert;
use App\Notifications\PortalLink;
use App\Notifications\QuoteReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** La copie cachée est ajoutée au moment de l'envoi réel (écouteur MessageSending), donc sans Mail::fake(). */
class MailCopyTest extends TestCase
{
    use RefreshDatabase;

    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.default' => 'array', 'queue.default' => 'sync']);
        CompanySetting::current()->update(['email' => 'mickael@nyleo.fr']);
        Event::listen(MessageSent::class, fn (MessageSent $e) => $this->sent[] = $e->message);
    }

    private function bcc(int $i = -1): array
    {
        $m = array_slice($this->sent, $i, 1)[0];

        return array_map(fn ($a) => $a->getAddress(), $m->getBcc());
    }

    public function test_client_emails_get_a_blind_copy_by_default(): void
    {
        $client = Client::create(['last_name' => 'Martin', 'email' => 'jean@example.com']);
        $client->notify(new QuoteReminder(Quote::create(['client_id' => $client->id])));
        Mail::to('jean@example.com')->send(new DocumentMail('Devis', "Bonjour,\n\nVoici le devis."));

        $this->assertCount(2, $this->sent);
        // E-mail suivi (relance) : la copie part à part, sans pixel ni lien suivi, pour ne pas compter les ouvertures de Mickaël.
        $this->assertSame([], $this->bcc(0));
        $copies = collect(Mail::mailer()->getSymfonyTransport()->messages())
            ->map(fn ($m) => $m->getOriginalMessage())
            ->filter(fn ($m) => ($m->getTo()[0] ?? null)?->getAddress() === 'mickael@nyleo.fr');
        $this->assertCount(1, $copies);
        $this->assertStringNotContainsString('/m/', $copies->first()->getHtmlBody());
        // E-mail non suivi : copie cachée classique.
        $this->assertSame(['mickael@nyleo.fr'], $this->bcc(1));
    }

    public function test_no_copy_when_disabled_or_for_login_links_and_admin_alerts(): void
    {
        $client = Client::create(['last_name' => 'Martin', 'email' => 'jean@example.com']);
        $user = User::create(['name' => 'M', 'email' => 'm@example.com', 'password' => 'x']);

        $client->notify(new PortalLink);
        $this->assertSame([], $this->bcc());
        $user->notify(new AdminAlert('Test', 'Corps', null));
        $this->assertSame([], $this->bcc());

        CompanySetting::current()->update(['bcc_enabled' => false]);
        Mail::to('jean@example.com')->send(new DocumentMail('Devis', 'Bonjour'));
        $this->assertSame([], $this->bcc());
    }

    public function test_custom_address_and_no_duplicate(): void
    {
        CompanySetting::current()->update(['bcc_email' => 'archive@nyleo.fr']);
        Mail::to('jean@example.com')->bcc('archive@nyleo.fr')->send(new DocumentMail('Devis', 'Bonjour'));
        $this->assertSame(['archive@nyleo.fr'], $this->bcc());

        Mail::to('archive@nyleo.fr')->send(new DocumentMail('Devis', 'Bonjour'));
        $this->assertSame([], $this->bcc());
    }
}
