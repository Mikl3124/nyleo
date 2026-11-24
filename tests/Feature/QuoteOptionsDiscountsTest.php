<?php

namespace Tests\Feature;

use App\Filament\Resources\Quotes\Pages\EditQuote;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Dossier;
use App\Models\Quote;
use App\Models\User;
use App\Services\CardPayment;
use App\Services\InvoiceService;
use App\Services\PdfService;
use App\Support\DefaultCatalog;
use App\Support\PaymentQr;
use App\Support\Totals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class QuoteOptionsDiscountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        CompanySetting::current()->update(['company_name' => 'M-GREEN', 'iban' => 'FR14 2004 1010 0505 0001 3M02 606']);
    }

    private function quote(): Quote
    {
        $client = Client::create(['first_name' => 'Jean', 'last_name' => 'Martin', 'email' => 'jean@example.com']);
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Maison', 'procedure' => 'PC']);
        $quote = Quote::create(['client_id' => $client->id, 'dossier_id' => $dossier->id, 'subject' => 'PC maison']);
        $quote->lines()->create(['label' => 'Permis de construire', 'quantity' => 1, 'unit_price' => 650, 'position' => 1]);
        $quote->lines()->create(['label' => 'Étude thermique RE2020', 'quantity' => 1, 'unit_price' => 280, 'position' => 2,
            'discount_type' => 'percent', 'discount_value' => 10]);
        $quote->lines()->create(['label' => 'Impression du dossier papier', 'quantity' => 1, 'unit_price' => 50, 'position' => 3, 'is_option' => true]);
        $quote->forceFill(['discount_type' => 'amount', 'discount_value' => 30])->save();
        $quote->recalculate();

        return $quote->refresh();
    }

    public function test_line_discount_option_and_global_discount_make_the_total(): void
    {
        $quote = $this->quote();

        // 650 + (280 − 28) = 902 ; − 30 € = 872 ; l'option (50 €) n'est pas comptée.
        $this->assertSame(252.0, (float) $quote->lines[1]->total_ht);
        $this->assertSame(30.0, (float) $quote->discount_amount);
        $this->assertSame(902.0, $quote->subtotal());
        $this->assertSame(872.0, (float) $quote->total_ttc);
        $this->assertSame(50.0, $quote->optionsTotal());
        $this->assertSame('Remise 10 %', $quote->lines[1]->discountLabel());
    }

    public function test_percent_discount_is_capped_and_amount_never_goes_negative(): void
    {
        $this->assertSame(['subtotal' => 100.0, 'discount' => 100.0, 'ht' => 0.0, 'vat' => 0.0, 'ttc' => 0.0, 'options' => 0.0],
            Totals::compute([['quantity' => 1, 'unit_price' => 100]], 'amount', 500));
        $this->assertSame(0.0, Totals::lineNet(['quantity' => 2, 'unit_price' => 50, 'discount_type' => 'percent', 'discount_value' => 150]));
        $this->assertSame(20.0, Totals::compute([['quantity' => 1, 'unit_price' => 100, 'vat_rate' => 20]], 'percent', 0)['vat']);
        // TVA répartie après remise globale.
        $this->assertSame(18.0, Totals::compute([['quantity' => 1, 'unit_price' => 100, 'vat_rate' => 20]], 'percent', 10)['vat']);
    }

    public function test_pdf_and_client_space_show_options_and_discounts(): void
    {
        $quote = $this->quote();

        $html = PdfService::quoteHtml($quote);
        $this->assertStringContainsString('Option', $html);
        $this->assertStringContainsString('ne sont pas comprises dans le total', $html);
        $this->assertStringContainsString('Remise 10', $html);
        $this->assertStringContainsString('Sous-total HT', $html);
        $this->assertStringContainsString('Options proposées (non comprises)', $html);
        $this->assertStringContainsString('872,00', $html);
    }

    public function test_invoice_keeps_discounts_and_card_and_qr_use_the_discounted_amount(): void
    {
        config(['services.stripe.secret' => 'sk_test_123']);
        $quote = $this->quote();
        $invoice = InvoiceService::createFinalFromQuote($quote);

        $this->assertSame('amount', $invoice->discount_type);
        $this->assertSame(872.0, (float) $invoice->total_ttc);
        $this->assertTrue($invoice->lines->firstWhere('label', 'Impression du dossier papier')->is_option);

        $invoice = InvoiceService::issue($invoice, notify: false)->refresh();
        $this->assertSame(872.0, $invoice->remaining());
        $this->assertStringContainsString('EUR872.00', PaymentQr::payload($invoice, CompanySetting::current()->snapshot()));
        $this->assertTrue(CardPayment::available($invoice->refresh()));

        // Avoir total : même montant, sans les options.
        $credit = InvoiceService::createCreditNote($invoice);
        $this->assertSame(872.0, (float) $credit->refresh()->total_ttc);
        $this->assertCount(2, $credit->lines);
    }

    public function test_partner_commission_uses_the_discounted_amount(): void
    {
        $quote = $this->quote();
        $quote->forceFill(['status' => 'signe'])->save();

        $this->assertSame(872.0, $quote->dossier->refresh()->amountHt());
    }

    public function test_thermal_study_is_in_the_catalogue_and_added_once_to_an_existing_one(): void
    {
        DefaultCatalog::seed();
        $this->assertSame(1, CatalogItem::where('category', 'RE2020')->count());
        $item = CatalogItem::where('category', 'RE2020')->first();
        $this->assertSame(280.0, (float) $item->unit_price);
        $this->assertStringContainsString('Documents remis :', $item->description);

        // La migration ne crée pas de doublon et ne touche pas au reste.
        $item->delete();
        CatalogItem::first()->update(['unit_price' => 999]);
        $migration = require database_path('migrations/2026_11_24_080100_catalog_etude_thermique_re2020.php');
        $migration->up();
        $migration->up();
        $this->assertSame(1, CatalogItem::where('category', 'RE2020')->count());
        $this->assertSame(999.0, (float) CatalogItem::first()->unit_price);
    }

    public function test_admin_form_saves_option_and_discounts(): void
    {
        $this->actingAs(User::factory()->create());
        $quote = $this->quote();

        Livewire::test(EditQuote::class, ['record' => $quote->getRouteKey()])
            ->call('startEditing')
            ->assertSee('Remise sur le total')
            ->assertSee('Option')
            ->set('data.discount_type', 'percent')
            ->set('data.discount_value', 50)
            ->call('save')
            ->assertHasNoFormErrors();

        // (650 + 252) − 50 % = 451
        $this->assertSame(451.0, (float) $quote->refresh()->total_ttc);
    }
}
