<?php

namespace Tests\Feature;

use App\Enums\DossierStatus;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\QuoteStatus;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Dossier;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Le statut et la prochaine étape d'un dossier suivent toujours ses devis et ses factures. */
class DossierStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        User::factory()->create(['email' => 'admin@nyleo.fr']);
        CompanySetting::current()->update(['company_name' => 'M-GREEN', 'siret' => '12345678900011', 'iban' => 'FR76 0000 0000 0000']);
    }

    private function client(): Client
    {
        return Client::create(['last_name' => 'Leveque', 'first_name' => 'Anne', 'type' => 'particulier', 'email' => 'leveque@example.com', 'city' => 'Ribérac', 'postal_code' => '24600']);
    }

    private function dossier(Client $client, DossierStatus $status = DossierStatus::Nouveau): Dossier
    {
        return Dossier::create(['client_id' => $client->id, 'title' => 'Véranda', 'procedure' => 'DP', 'status' => $status]);
    }

    private function invoice(Client $client, ?Dossier $dossier = null): Invoice
    {
        $invoice = Invoice::create(['type' => InvoiceType::Facture, 'status' => InvoiceStatus::Brouillon, 'client_id' => $client->id, 'dossier_id' => $dossier?->id, 'subject' => 'Déclaration préalable']);
        $invoice->lines()->create(['label' => 'Déclaration préalable', 'quantity' => 1, 'unit_price' => 400]);
        $invoice->recalculate();

        return $invoice->refresh();
    }

    private function quote(Client $client, Dossier $dossier): Quote
    {
        $quote = Quote::create(['client_id' => $client->id, 'dossier_id' => $dossier->id, 'subject' => 'DP véranda']);
        $quote->lines()->create(['label' => 'Déclaration préalable', 'quantity' => 1, 'unit_price' => 400]);
        $quote->recalculate();

        return $quote->refresh();
    }

    public function test_an_invoice_takes_the_dossier_out_of_new_request(): void
    {
        $client = $this->client();
        $dossier = $this->dossier($client);
        $this->assertSame('Rappeler le client et faire le devis', $dossier->nextAction());

        $invoice = $this->invoice($client, $dossier);
        $this->assertSame(DossierStatus::PlansEnCours, $dossier->fresh()->status);

        InvoiceService::issue($invoice, notify: false);
        $dossier->refresh();
        $this->assertSame(DossierStatus::PlansEnCours, $dossier->status);
        $this->assertSame('Finir les plans et les envoyer au client', $dossier->nextAction());
        $this->assertSame("Le client doit régler la facture {$invoice->fresh()->number}", $dossier->waitingFor());
    }

    public function test_invoice_created_from_client_page_goes_into_the_current_dossier(): void
    {
        $this->actingAs(User::first());
        $client = $this->client();
        $dossier = $this->dossier($client);

        Livewire::withQueryParams(['client' => $client->id])->test(CreateInvoice::class)
            ->assertSet('data.dossier_id', $dossier->id);

        $invoice = $this->invoice($client);
        InvoiceService::ensureDossier($invoice);
        $this->assertSame($dossier->id, $invoice->fresh()->dossier_id);
        $this->assertSame(DossierStatus::PlansEnCours, $dossier->fresh()->status);
    }

    public function test_invoice_for_a_client_without_dossier_creates_one(): void
    {
        $client = $this->client();
        $invoice = $this->invoice($client);
        InvoiceService::ensureDossier($invoice);

        $dossier = $invoice->fresh()->dossier;
        $this->assertNotNull($dossier);
        $this->assertSame(DossierStatus::PlansEnCours, $dossier->status);
    }

    public function test_quote_lifecycle_drives_the_status(): void
    {
        $client = $this->client();
        $dossier = $this->dossier($client);
        $quote = $this->quote($client, $dossier);
        $this->assertSame("Finir le devis et l'envoyer au client", $dossier->fresh()->nextAction());

        QuoteService::send($quote, notify: false);
        $this->assertSame(DossierStatus::DevisEnvoye, $dossier->fresh()->status);
        $this->assertSame('Le client doit signer le devis', $dossier->fresh()->waitingFor());

        $quote->update(['status' => QuoteStatus::Refuse]);
        $this->assertSame(DossierStatus::Nouveau, $dossier->fresh()->status);
        $this->assertSame('Devis refusé : rappeler le client', $dossier->fresh()->nextAction());

        $quote->update(['status' => QuoteStatus::Signe]);
        $this->assertSame(DossierStatus::PiecesAttendues, $dossier->fresh()->status);

        $quote->delete();
        $this->assertSame(DossierStatus::Nouveau, $dossier->fresh()->status);
    }

    public function test_expired_quote_is_not_waiting_for_a_signature(): void
    {
        $client = $this->client();
        $dossier = $this->dossier($client);
        $quote = $this->quote($client, $dossier);
        QuoteService::send($quote, notify: false);
        $quote->update(['valid_until' => now()->subDay()]);

        $dossier->refresh();
        $this->assertNull($dossier->waitingFor());
        $this->assertSame('Devis expiré : relancer le client ou refaire le devis', $dossier->nextAction());
    }

    public function test_work_in_progress_is_never_pulled_back_by_a_document(): void
    {
        $client = $this->client();
        $dossier = $this->dossier($client, DossierStatus::EnInstruction);
        $quote = $this->quote($client, $dossier);
        $quote->update(['status' => QuoteStatus::Signe]);
        $this->invoice($client, $dossier);

        $this->assertSame(DossierStatus::EnInstruction, $dossier->fresh()->status);
    }

    public function test_plans_validated_waits_for_payment_before_deposit(): void
    {
        $client = $this->client();
        $dossier = $this->dossier($client, DossierStatus::PlansValides);
        $invoice = $this->invoice($client, $dossier);
        InvoiceService::issue($invoice, notify: false);

        $dossier->refresh();
        $this->assertNull($dossier->nextAction());
        $this->assertStringStartsWith('Le client doit régler la facture', $dossier->waitingFor());

        InvoiceService::addPayment($invoice->fresh(), 400, 'virement', notify: false);
        $this->assertSame('Déposer en mairie', $dossier->fresh()->nextAction());
    }

    public function test_command_repairs_existing_dossiers(): void
    {
        $client = $this->client();
        $dossier = $this->dossier($client);
        $orphan = Invoice::withoutEvents(fn () => $this->invoice($client));
        // État d'avant le correctif : facture émise sans dossier, dossier resté en « Nouvelle demande ».
        Invoice::whereKey($orphan->id)->update(['number' => 'F-643', 'status' => 'emise']);
        Dossier::whereKey($dossier->id)->update(['status' => 'nouveau']);

        $this->artisan('nyleo:sync-dossiers', ['--dry-run' => true])->assertSuccessful();
        $this->assertNull($orphan->fresh()->dossier_id);

        $this->artisan('nyleo:sync-dossiers')->assertSuccessful();
        $this->assertSame($dossier->id, $orphan->fresh()->dossier_id);
        $this->assertSame(DossierStatus::PlansEnCours, $dossier->fresh()->status);
    }
}
