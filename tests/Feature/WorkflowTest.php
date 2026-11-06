<?php

namespace Tests\Feature;

use App\Enums\DocumentCategory;
use App\Enums\DossierStatus;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\QuoteStatus;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Dossier;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\ClientNotification;
use App\Notifications\InvoiceReminder;
use App\Notifications\InvoiceSent;
use App\Notifications\PaymentReceived;
use App\Notifications\QuoteSent;
use App\Services\DossierWorkflow;
use App\Services\InvoiceService;
use App\Services\QuoteService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        User::factory()->create(['email' => 'admin@nyleo.fr']);
        CompanySetting::current()->update([
            'company_name' => 'Mickaël Delpech', 'siret' => '12345678900011', 'iban' => 'FR76 0000 0000 0000',
            'cheque_order' => 'Mickaël Delpech', 'quote_next_number' => 134, 'invoice_next_number' => 117,
        ]);
    }

    private function makeQuote(float $deposit = 0): Quote
    {
        $client = Client::firstOrCreate(['email' => 'jean@example.com'], ['first_name' => 'Jean', 'last_name' => 'Martin', 'city' => 'Ribérac']);
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda', 'procedure' => 'DP', 'site_postal_code' => '24600']);
        $quote = Quote::create(['client_id' => $client->id, 'dossier_id' => $dossier->id, 'subject' => 'DP véranda', 'deposit_percent' => $deposit]);
        $quote->lines()->create(['label' => 'Déclaration préalable — véranda', 'quantity' => 1, 'unit_price' => 400]);
        $quote->recalculate();

        return $quote->refresh();
    }

    public function test_full_dossier_workflow(): void
    {
        $quote = $this->makeQuote();
        $this->assertSame('24', $quote->dossier->department);

        QuoteService::send($quote);
        $this->assertSame(QuoteStatus::Envoye, $quote->status);
        $this->assertSame('D'.now()->format('Ym').'-134', $quote->number);
        Notification::assertSentTo($quote->client, QuoteSent::class);

        // Le client se connecte avec son lien magique et signe
        $link = ClientNotification::portalUrl($quote->client, route('portal.quotes.show', $quote, false));
        $this->get($link)->assertRedirect(route('portal.quotes.show', $quote));
        $this->get(route('portal.quotes.show', $quote))->assertOk()->assertSee('Je signe le devis')->assertSee('Pourquoi je vous demande ces informations')
            ->assertSee('sans attendre la fin de mon délai de rétractation')->assertSee('href="'.route('cgv').'"', false);
        $this->post(route('portal.quotes.sign', $quote), ['signer_name' => 'Jean Martin', 'accept' => '1'])
            ->assertSessionHasErrors(['birth_date', 'birth_place']);
        $this->post(route('portal.quotes.sign', $quote), ['signer_name' => 'Jean Martin', 'birth_date' => '1958-07-18', 'birth_place' => 'Périgueux (24)', 'accept' => '1', 'early_start' => '1'])
            ->assertRedirect(route('portal.dossiers.show', $quote->dossier));
        $this->assertSame('Périgueux (24)', $quote->client->fresh()->birth_place);
        $this->assertSame('1958-07-18', $quote->client->fresh()->birth_date->format('Y-m-d'));

        $quote->refresh();
        $this->assertSame(QuoteStatus::Signe, $quote->status);
        $this->assertNotNull($quote->signature_hash);
        $this->assertTrue($quote->early_start_requested);
        $this->assertTrue($quote->dossier->events()->where('type', 'demarrage_anticipe')->exists());
        Storage::disk('local')->assertExists($quote->signed_pdf_path);
        $this->assertSame(DossierStatus::PiecesAttendues, $quote->dossier->fresh()->status);

        // Le client envoie des photos et ses infos CERFA
        $this->post(route('portal.dossiers.upload', $quote->dossier), ['files' => [UploadedFile::fake()->image('maison.jpg')]])->assertSessionHas('status');
        $this->assertSame(1, $quote->dossier->documents()->where('category', 'photo')->count());
        $this->post(route('portal.dossiers.info', $quote->dossier), ['birth_date' => '1958-07-18', 'birth_place' => 'Le Mont-Dore', 'cadastre_number' => '756']);
        $this->assertSame('Le Mont-Dore', $quote->client->fresh()->birth_place);
        $this->assertSame('756', $quote->dossier->fresh()->cadastre_number);

        // Plans envoyés pour validation, le client valide → facture émise automatiquement
        Storage::disk('local')->put('dossiers/plan.pdf', 'pdf');
        $plan = $quote->dossier->documents()->create(['category' => DocumentCategory::Plan, 'name' => 'Plans DP', 'path' => 'dossiers/plan.pdf']);
        $this->assertSame(1, $plan->version);
        DossierWorkflow::submitPlans($plan);
        $this->assertSame(DossierStatus::PlansAValider, $quote->dossier->fresh()->status);
        $this->get(route('portal.dossiers.show', $quote->dossier))->assertOk()->assertSee('Je valide les plans');
        $this->post(route('portal.documents.validate', $plan))->assertSessionHas('status');

        $invoice = Invoice::where('quote_id', $quote->id)->where('type', 'facture')->first();
        $this->assertNotNull($invoice);
        $this->assertSame(InvoiceStatus::Emise, $invoice->status);
        $this->assertSame('F'.now()->format('Ym').'-117', $invoice->number);
        $this->assertEquals(400, (float) $invoice->amount_due);
        Storage::disk('local')->assertExists($invoice->pdf_path);
        Notification::assertSentTo($quote->client, InvoiceSent::class);
        $this->get(route('portal.dossiers.show', $quote->dossier))->assertSee('FR76 0000 0000 0000');

        // Paiement par chèque
        InvoiceService::addPayment($invoice, 400, 'cheque', now(), 'Chèque 123');
        $this->assertSame(InvoiceStatus::Payee, $invoice->fresh()->status);
        Notification::assertSentTo($quote->client, PaymentReceived::class);

        // Dépôt en mairie : délai DP = 1 mois
        DossierWorkflow::markDeposited($quote->dossier->fresh(), Carbon::parse('2026-11-10'), 'DP 024 352 26 00012', 'portail');
        $dossier = $quote->dossier->fresh();
        $this->assertSame(DossierStatus::EnInstruction, $dossier->status);
        $this->assertSame('2026-12-10', $dossier->decision_due_date->toDateString());

        DossierWorkflow::markIncomplete($dossier, Carbon::parse('2026-11-20'), 'Plan de coupe');
        $this->assertSame('2027-02-20', $dossier->fresh()->incomplete_deadline->toDateString());
        DossierWorkflow::markCompleted($dossier->fresh(), Carbon::parse('2026-12-01'));
        $this->assertSame('2027-01-01', $dossier->fresh()->decision_due_date->toDateString());
        DossierWorkflow::markDecision($dossier->fresh(), true, Carbon::parse('2026-12-20'));
        $this->assertSame(DossierStatus::Accorde, $dossier->fresh()->status);
    }

    public function test_deposit_invoice_on_signature_and_deduction_on_final(): void
    {
        $quote = $this->makeQuote(deposit: 30);
        QuoteService::send($quote);
        QuoteService::sign($quote->refresh(), 'Jean Martin', '1.2.3.4', 'test');

        $deposit = Invoice::where('type', InvoiceType::Acompte->value)->first();
        $this->assertNotNull($deposit);
        $this->assertNotNull($deposit->number);
        $this->assertEquals(120, (float) $deposit->total_ttc);

        $final = InvoiceService::createFinalFromQuote($quote->refresh());
        $this->assertEquals(120, (float) $final->deposits_deducted);
        $this->assertEquals(280, (float) $final->amount_due);
    }

    public function test_credit_note_cancels_invoice_and_numbering_is_continuous(): void
    {
        $quote = $this->makeQuote();
        $invoice = InvoiceService::createFinalFromQuote($quote);
        InvoiceService::issue($invoice, notify: false);
        $credit = InvoiceService::createCreditNote($invoice->refresh(), null, 'Erreur');
        InvoiceService::issue($credit, notify: false);

        $this->assertSame('AV'.now()->format('Ym').'-1', $credit->number);
        $this->assertSame(InvoiceStatus::Annulee, $invoice->fresh()->status);

        $second = InvoiceService::createFinalFromQuote($quote->refresh());
        InvoiceService::issue($second, notify: false);
        $this->assertSame('F'.now()->format('Ym').'-118', $second->number);

        $this->expectException(\LogicException::class);
        $invoice->fresh()->delete();
    }

    public function test_invoice_is_due_ten_days_after_issue_by_default(): void
    {
        Carbon::setTestNow('2026-11-06 09:00');
        $invoice = InvoiceService::createFinalFromQuote($this->makeQuote());
        InvoiceService::issue($invoice, notify: false);

        $this->assertSame(10, CompanySetting::current()->payment_terms_days);
        $this->assertSame('2026-11-16', $invoice->fresh()->due_date->toDateString());
        Carbon::setTestNow();
    }

    public function test_test_invoice_can_be_deleted_with_payments_and_credit_notes_and_number_is_reused(): void
    {
        $ym = now()->format('Ym');
        $quote = $this->makeQuote();
        $invoice = InvoiceService::createFinalFromQuote($quote);
        InvoiceService::issue($invoice, notify: false);
        InvoiceService::addPayment($invoice->refresh(), 100, 'cheque', notify: false);
        $credit = InvoiceService::createCreditNote($invoice->refresh(), 50, 'Essai');
        InvoiceService::issue($credit, notify: false);
        $pdf = $invoice->fresh()->pdf_path;
        Storage::disk('local')->assertExists($pdf);

        // Supprimer l'avoir seul : la facture redevient due et le n° d'avoir est rendu.
        InvoiceService::delete($credit->fresh());
        $this->assertSame(400.0, (float) $invoice->fresh()->amount_due);
        $this->assertSame(1, CompanySetting::current()->credit_note_next_number);

        InvoiceService::delete($invoice->fresh());
        $this->assertSame(0, Invoice::withTrashed()->count());
        $this->assertSame(0, Payment::count());
        Storage::disk('local')->assertMissing($pdf);
        $this->assertSame(117, CompanySetting::current()->invoice_next_number);

        $again = InvoiceService::createFinalFromQuote($quote->refresh());
        InvoiceService::issue($again, notify: false);
        $this->assertSame("F{$ym}-117", $again->number);
    }

    public function test_deleting_an_older_invoice_keeps_the_counter(): void
    {
        $first = InvoiceService::createFinalFromQuote($this->makeQuote());
        InvoiceService::issue($first, notify: false);
        $second = InvoiceService::createFinalFromQuote($this->makeQuote());
        InvoiceService::issue($second, notify: false);

        InvoiceService::delete($first->fresh());
        $this->assertSame(119, CompanySetting::current()->invoice_next_number);
    }

    public function test_reset_invoices_command_deletes_everything_and_restarts_numbering(): void
    {
        $invoice = InvoiceService::createFinalFromQuote($this->makeQuote());
        InvoiceService::issue($invoice, notify: false);
        InvoiceService::issue(InvoiceService::createCreditNote($invoice->refresh()), notify: false);
        InvoiceService::createFinalFromQuote($this->makeQuote());

        $this->artisan('nyleo:reset-invoices', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, Invoice::withTrashed()->count());
        $this->assertSame(1, CompanySetting::current()->invoice_next_number);
        $this->assertSame(1, CompanySetting::current()->credit_note_next_number);
        $this->assertSame(134, CompanySetting::current()->quote_next_number);
    }

    public function test_reminders_command_relances_unpaid_invoices(): void
    {
        $quote = $this->makeQuote();
        $invoice = InvoiceService::createFinalFromQuote($quote);
        InvoiceService::issue($invoice, notify: false);
        $invoice->refresh()->forceFill(['due_date' => now()->subDays(10)])->save();

        $this->artisan('nyleo:reminders')->assertSuccessful();
        Notification::assertSentTo($quote->client, InvoiceReminder::class);
        $this->assertSame(1, $invoice->fresh()->reminder_count);

        // Pas de nouvelle relance avant l'intervalle
        $this->artisan('nyleo:reminders')->assertSuccessful();
        $this->assertSame(1, $invoice->fresh()->reminder_count);
    }

    public function test_client_cannot_see_another_clients_dossier(): void
    {
        $quote = $this->makeQuote();
        $other = Client::create(['last_name' => 'Autre', 'email' => 'autre@example.com']);
        $this->actingAs($other, 'client')->get(route('portal.dossiers.show', $quote->dossier))->assertNotFound();
        $this->actingAs($other, 'client')->get(route('portal.quotes.pdf', $quote))->assertNotFound();
    }

    public function test_guest_is_redirected_and_magic_link_requires_signature(): void
    {
        $this->get(route('portal.home'))->assertRedirect(route('portal.login'));
        $client = Client::create(['last_name' => 'X', 'email' => 'x@example.com']);
        $this->get(route('portal.magic', $client))->assertForbidden();
    }

    public function test_site_request_creates_client_and_dossier(): void
    {
        $this->get('/')->assertOk()->assertSee('Demander un devis');
        $this->post(route('site.request'), [
            'last_name' => 'Durand', 'email' => 'durand@example.com', 'phone' => '0600000000',
            'procedure' => 'DP', 'nature' => 'pergola', 'site_city' => 'Libourne', 'site_postal_code' => '33500',
        ])->assertRedirect();
        $this->assertDatabaseHas('dossiers', ['site_city' => 'Libourne', 'department' => '33', 'procedure' => 'DP']);
    }
}
