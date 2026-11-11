<?php

namespace Tests\Feature;

use App\Filament\Resources\Dossiers\Pages\ViewDossier;
use App\Filament\Resources\Dossiers\RelationManagers\DocumentsRelationManager;
use App\Mail\DocumentMail;
use App\Models\Client;
use App\Models\Document;
use App\Models\Dossier;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\DossierUpdate;
use App\Services\DocumentMailer;
use App\Services\InvoiceService;
use App\Services\PlanAccess;
use App\Services\PlanPreview;
use App\Support\EmailTemplates;
use App\Support\MailTracking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PlanAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
    }

    /** @return array{Invoice, Document} */
    private function dossierWithPlanAndInvoice(bool $issue = true): array
    {
        $client = Client::create(['first_name' => 'Jean', 'last_name' => 'Martin', 'email' => 'jean'.uniqid().'@example.com',
            'birth_date' => '1960-05-12', 'birth_place' => 'Périgueux (24)']);
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda', 'procedure' => 'DP']);
        Storage::disk('local')->put("dossiers/{$dossier->id}/plans.pdf", Pdf::loadHTML('<h1>Plan de masse</h1><p>Échelle 1/200</p>')->output());
        $plan = Document::create(['dossier_id' => $dossier->id, 'category' => 'plan', 'name' => 'Plans DP véranda',
            'path' => "dossiers/{$dossier->id}/plans.pdf", 'original_name' => 'plans.pdf', 'visible_to_client' => true, 'uploaded_by' => 'admin']);

        $quote = Quote::create(['client_id' => $client->id, 'dossier_id' => $dossier->id, 'subject' => 'DP véranda']);
        $quote->lines()->create(['label' => 'Déclaration préalable', 'quantity' => 1, 'unit_price' => 400]);
        $quote->recalculate();
        $invoice = InvoiceService::createFinalFromQuote($quote->refresh());
        if ($issue) {
            InvoiceService::issue($invoice, notify: false);
        }

        return [$invoice->refresh(), $plan->refresh()];
    }

    public function test_plans_unlock_only_when_every_invoice_is_paid(): void
    {
        [$invoice, $plan] = $this->dossierWithPlanAndInvoice(issue: false);
        $this->assertTrue(PlanAccess::isProtected($plan), 'Sans facture émise, les plans restent protégés.');

        InvoiceService::issue($invoice, notify: false);
        $this->assertTrue(PlanAccess::isProtected($plan->refresh()));

        InvoiceService::addPayment($invoice->refresh(), 100, 'virement', now(), notify: false);
        $this->assertTrue(PlanAccess::isProtected($plan->refresh()), 'Un paiement partiel ne débloque pas.');

        InvoiceService::addPayment($invoice->refresh(), 300, 'virement', now(), notify: false);
        $this->assertFalse(PlanAccess::isProtected($plan->refresh()));

        $photo = Document::create(['dossier_id' => $plan->dossier_id, 'category' => 'cerfa', 'name' => 'CERFA', 'path' => 'x.pdf', 'visible_to_client' => true]);
        $this->assertFalse(PlanAccess::isProtected($photo), 'Seuls les plans sont protégés.');
    }

    public function test_manual_unlock(): void
    {
        [, $plan] = $this->dossierWithPlanAndInvoice();
        $plan->dossier->update(['plans_unlocked_at' => now()]);

        $this->assertFalse(PlanAccess::isProtected($plan->refresh()));
    }

    public function test_client_sees_watermarked_pages_but_cannot_download_until_paid(): void
    {
        [$invoice, $plan] = $this->dossierWithPlanAndInvoice();
        $client = $invoice->client;

        $this->actingAs($client, 'client')->get(route('portal.documents.download', $plan))
            ->assertRedirect(route('portal.documents.view', $plan));

        $this->actingAs($client, 'client')->get(route('portal.documents.view', $plan))
            ->assertOk()->assertSee(route('portal.documents.page', [$plan, 1]), false)
            ->assertDontSee('Télécharger')->assertDontSee('paiement')->assertDontSee('règlement');

        $page = $this->actingAs($client, 'client')->get(route('portal.documents.page', [$plan, 1]));
        $page->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertStringContainsString('no-store', (string) $page->headers->get('Cache-Control'));
        $this->assertSame(1, PlanPreview::pageCount($plan));
        $this->assertStringContainsString('Jean Martin', PlanPreview::watermarkText($plan));

        $this->actingAs($client, 'client')->get(route('portal.dossiers.show', $plan->dossier))
            ->assertOk()->assertSee(route('portal.documents.view', $plan), false);

        // Facture réglée : le PDF original se télécharge, la visionneuse renvoie vers lui.
        InvoiceService::addPayment($invoice, 400, 'cheque', now(), notify: false);
        $this->actingAs($client, 'client')->get(route('portal.documents.download', $plan))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($client, 'client')->get(route('portal.documents.view', $plan))->assertRedirect(route('portal.documents.download', $plan));
        $this->actingAs($client, 'client')->get(route('portal.documents.page', [$plan, 1]))->assertNotFound();
        $this->actingAs($client, 'client')->get(route('portal.dossiers.show', $plan->dossier))
            ->assertOk()->assertSee('Télécharger');
    }

    public function test_another_client_cannot_see_the_plans(): void
    {
        [, $plan] = $this->dossierWithPlanAndInvoice();
        $other = Client::create(['last_name' => 'Autre', 'email' => 'autre@example.com']);

        $this->actingAs($other, 'client')->get(route('portal.documents.view', $plan))->assertNotFound();
        $this->actingAs($other, 'client')->get(route('portal.documents.page', [$plan, 1]))->assertNotFound();
    }

    public function test_old_email_download_link_opens_the_viewer_instead(): void
    {
        [, $plan] = $this->dossierWithPlanAndInvoice();

        $response = $this->get(MailTracking::downloadUrl($plan));
        $response->assertRedirect();
        $this->assertStringContainsString('/espace/acces/'.$plan->dossier->client_id, $response->headers->get('Location'));
        $this->assertStringContainsString(urlencode('/espace/documents/'.$plan->id.'/consulter'), $response->headers->get('Location'));
    }

    public function test_invoice_email_links_the_plans_online_instead_of_attaching_them(): void
    {
        Mail::fake();
        [$invoice, $plan] = $this->dossierWithPlanAndInvoice();

        DocumentMailer::sendInvoice($invoice, ['to' => $invoice->client->email, 'documents' => [$plan->id]] + EmailTemplates::forInvoice($invoice));

        Mail::assertSent(DocumentMail::class, function (DocumentMail $m) {
            $names = array_column($m->files, 'name');

            return count($names) === 1 && str_ends_with($names[0], '.pdf') && ! str_contains($names[0], 'Plans')
                && count($m->online) === 1 && $m->online[0]['label'] === 'Voir les plans'
                && ! collect($m->downloads)->contains(fn ($d) => str_contains($d['label'], 'Plans'));
        });

        // Une fois réglée, les plans redeviennent une pièce jointe normale.
        InvoiceService::addPayment($invoice->refresh(), 400, 'virement', now(), notify: false);
        DocumentMailer::sendInvoice($invoice->refresh(), ['to' => $invoice->client->email, 'documents' => [$plan->id]] + EmailTemplates::forInvoice($invoice));
        Mail::assertSent(DocumentMail::class, fn (DocumentMail $m) => $m->online === [] && in_array('Plans DP véranda.pdf', array_column($m->files, 'name'), true));
    }

    public function test_plans_email_has_no_pdf_download_while_protected(): void
    {
        [, $plan] = $this->dossierWithPlanAndInvoice();
        $html = (string) (new DossierUpdate($plan->dossier, 'Vos plans sont prêts', ['Les plans sont prêts.'], 'Consulter mes plans', $plan))
            ->toMail($plan->dossier->client)->render();

        $this->assertStringNotContainsString('Télécharger', $html);
        $this->assertStringContainsString('Consulter mes plans', $html);
    }

    public function test_admin_can_unlock_and_lock_again(): void
    {
        $this->actingAs(User::factory()->create());
        [, $plan] = $this->dossierWithPlanAndInvoice();
        $dossier = $plan->dossier;

        Livewire::test(DocumentsRelationManager::class, ['ownerRecord' => $dossier, 'pageClass' => ViewDossier::class])
            ->assertSee('Consultation seule')
            ->callTableAction('unlockPlans');
        $this->assertNotNull($dossier->refresh()->plans_unlocked_at);
        $this->assertFalse(PlanAccess::isProtected($plan->refresh()));

        Livewire::test(DocumentsRelationManager::class, ['ownerRecord' => $dossier, 'pageClass' => ViewDossier::class])
            ->assertSee('Téléchargeable')
            ->callTableAction('lockPlans');
        $this->assertNull($dossier->refresh()->plans_unlocked_at);
    }

    public function test_plans_uploaded_in_the_send_window_are_stored_and_shown_online_only(): void
    {
        Mail::fake();
        [$invoice] = $this->dossierWithPlanAndInvoice();
        Storage::disk('local')->put('email-attachments/abc.pdf', Pdf::loadHTML('<h1>Plan V2</h1>')->output());
        $template = EmailTemplates::forInvoice($invoice);
        $this->assertStringContainsString('« Voir les plans »', $template['body']);
        $this->assertStringNotContainsString('pièce jointe les plans', $template['body']);

        DocumentMailer::sendInvoice($invoice, ['to' => $invoice->client->email, 'plans' => ['email-attachments/abc.pdf'],
            'plan_names' => ['email-attachments/abc.pdf' => 'V2 - DP - MARTIN.pdf']] + $template);

        $doc = $invoice->dossier->documents()->latest('id')->first();
        $this->assertSame('plan', $doc->category->value);
        $this->assertTrue($doc->visible_to_client);
        $this->assertSame('V2 - DP - MARTIN.pdf', $doc->original_name);
        $this->assertTrue(Storage::disk('local')->exists($doc->path));
        $this->assertFalse(Storage::disk('local')->exists('email-attachments/abc.pdf'));

        Mail::assertSent(DocumentMail::class, fn (DocumentMail $m) => array_column($m->online, 'label') === ['Voir les plans']
            && ! collect($m->files)->contains(fn ($f) => str_contains($f['name'], 'V2'))
            && str_contains($m->online[0]['url'], urlencode('/espace/documents/'.$doc->id.'/consulter')));
    }

    public function test_old_custom_text_is_adapted_when_plans_are_online(): void
    {
        Mail::fake();
        [$invoice, $plan] = $this->dossierWithPlanAndInvoice();

        DocumentMailer::sendInvoice($invoice, ['to' => $invoice->client->email, 'documents' => [$plan->id],
            'subject' => 'Plans + facture', 'body' => "Bonjour,\n\nVeuillez trouver en pièce jointe les plans établis pour votre déclaration."]);

        Mail::assertSent(DocumentMail::class, fn (DocumentMail $m) => str_contains($m->body, 'consulter en ligne') && ! str_contains($m->body, 'pièce jointe les plans'));
    }

    public function test_birth_date_and_place_are_asked_before_showing_the_plans(): void
    {
        [$invoice, $plan] = $this->dossierWithPlanAndInvoice();
        $client = $invoice->client;
        $client->update(['birth_date' => null, 'birth_place' => null]);
        $this->assertStringNotContainsString('date et lieu de naissance', EmailTemplates::forInvoice($invoice)['body']);

        $this->actingAs($client, 'client')->get(route('portal.documents.view', $plan))
            ->assertOk()->assertSee('Avant d')->assertDontSee(route('portal.documents.page', [$plan, 1]), false);
        $this->actingAs($client, 'client')->get(route('portal.documents.page', [$plan, 1]))->assertForbidden();

        $this->actingAs($client, 'client')->post(route('portal.documents.birth', $plan), ['birth_date' => '1955-03-02', 'birth_place' => 'Sourzac (24)'])
            ->assertRedirect(route('portal.documents.view', $plan));
        $this->assertSame('Sourzac (24)', $client->refresh()->birth_place);
        $this->actingAs($client, 'client')->get(route('portal.documents.view', $plan))
            ->assertOk()->assertSee(route('portal.documents.page', [$plan, 1]), false);
    }
}
