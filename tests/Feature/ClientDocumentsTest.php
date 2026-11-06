<?php

namespace Tests\Feature;

use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Quotes\Pages\CreateQuote;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_page_opens_quote_and_invoice_forms_with_the_client_chosen(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::create(['last_name' => 'Martin', 'email' => 'm@example.com']);
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Piscine', 'procedure' => 'DP']);
        $params = ['client' => $client->id, 'dossier' => $dossier->id];

        Livewire::test(EditClient::class, ['record' => $client->getRouteKey()])
            ->assertActionVisible('newQuote')
            ->assertActionHasUrl('newQuote', QuoteResource::getUrl('create', $params))
            ->assertActionVisible('newInvoice')
            ->assertActionHasUrl('newInvoice', InvoiceResource::getUrl('create', $params))
            ->assertSee('Nouveau devis')
            ->assertSee('Nouvelle facture');

        Livewire::withQueryParams($params)->test(CreateQuote::class)->assertSchemaStateSet(['client_id' => $client->id, 'dossier_id' => $dossier->id]);
        Livewire::withQueryParams($params)->test(CreateInvoice::class)->assertSchemaStateSet(['client_id' => $client->id, 'dossier_id' => $dossier->id]);
    }

    public function test_dossier_is_left_empty_when_the_client_has_several(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::create(['last_name' => 'Durand']);
        Dossier::create(['client_id' => $client->id, 'title' => 'Garage', 'procedure' => 'DP']);
        Dossier::create(['client_id' => $client->id, 'title' => 'Maison', 'procedure' => 'PC']);

        Livewire::test(EditClient::class, ['record' => $client->getRouteKey()])
            ->assertActionHasUrl('newQuote', QuoteResource::getUrl('create', ['client' => $client->id]));
    }
}
