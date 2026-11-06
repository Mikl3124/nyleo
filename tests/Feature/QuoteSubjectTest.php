<?php

namespace Tests\Feature;

use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Quotes\Pages\CreateQuote;
use App\Filament\Resources\Quotes\Pages\EditQuote;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuoteSubjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_choosing_a_prestation_fills_the_subject_without_the_price(): void
    {
        $this->actingAs(User::factory()->create());
        $dp = CatalogItem::create(['label' => 'Déclaration préalable — véranda', 'unit' => 'forfait', 'unit_price' => 450, 'active' => true]);
        $pc = CatalogItem::create(['label' => 'Permis de construire — maison', 'unit' => 'forfait', 'unit_price' => 1200, 'active' => true]);

        foreach ([CreateQuote::class, CreateInvoice::class] as $page) {
            $lw = Livewire::test($page);
            $key = array_key_first($lw->get('data.lines'));

            $lw->set("data.lines.$key.catalog_item", $dp->id)
                ->assertSet('data.subject', 'Déclaration préalable — véranda')
                ->assertSet("data.lines.$key.unit_price", 450);

            // Changement de prestation : l'objet suit tant qu'il n'a pas été retouché.
            $lw->set("data.lines.$key.catalog_item", $pc->id)
                ->assertSet('data.subject', 'Permis de construire — maison');

            // Objet modifié à la main : on ne l'écrase plus.
            $lw->set('data.subject', 'Mon objet')
                ->set("data.lines.$key.catalog_item", $dp->id)
                ->assertSet('data.subject', 'Mon objet');
        }
    }

    public function test_created_quote_opens_on_preview_with_edit_button(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::create(['last_name' => 'Brown', 'type' => 'particulier', 'email' => 'brown@example.com', 'city' => 'Périgueux', 'postal_code' => '24000']);
        $quote = Quote::create(['client_id' => $client->id]);
        $quote->lines()->create(['label' => 'Déclaration préalable — véranda', 'quantity' => 1, 'unit' => 'forfait', 'unit_price' => 400, 'position' => 1]);
        $quote->recalculate();
        QuoteResource::fillSubjectFromLines($quote);
        $this->assertSame('Déclaration préalable — véranda', $quote->fresh()->subject);

        Livewire::test(EditQuote::class, ['record' => $quote->getRouteKey()])
            ->assertSet('editing', false)
            ->assertSee('Aperçu du devis')
            ->assertSee('srcdoc', false)
            ->call('startEditing')
            ->assertSet('editing', true)
            ->assertSet('data.subject', 'Déclaration préalable — véranda')
            ->assertSee('brown@example.com')
            ->set('data.subject', 'DP véranda')
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertSet('editing', false);

        $this->assertSame('DP véranda', $quote->fresh()->subject);
    }

    public function test_client_can_be_edited_from_the_quote(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::create(['last_name' => 'Brown', 'type' => 'particulier']);
        $quote = Quote::create(['client_id' => $client->id]);

        Livewire::test(EditQuote::class, ['record' => $quote->getRouteKey()])
            ->mountFormComponentAction('client_id', 'editOption')
            ->setFormComponentActionData(['last_name' => 'Brown', 'type' => 'particulier', 'email' => 'zach@example.com'])
            ->callMountedFormComponentAction()
            ->assertHasNoFormComponentActionErrors();

        $this->assertSame('zach@example.com', $client->fresh()->email);
    }
}
