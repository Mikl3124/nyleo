<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Dossier;
use App\Models\Quote;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_render(): void
    {
        Storage::fake('local');
        Notification::fake();
        $this->seed();
        $user = User::factory()->create();
        $client = Client::create(['last_name' => 'Martin', 'email' => 'm@example.com']);
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Pergola', 'procedure' => 'DP']);
        $quote = Quote::create(['client_id' => $client->id, 'dossier_id' => $dossier->id]);
        $quote->lines()->create(['label' => 'DP', 'quantity' => 1, 'unit_price' => 400]);
        $quote->recalculate();
        $invoice = InvoiceService::createFinalFromQuote($quote);

        $this->actingAs($user);
        foreach ([
            '/admin', '/admin/clients', '/admin/clients/create', "/admin/clients/{$client->id}/edit",
            '/admin/dossiers', '/admin/dossiers/create', "/admin/dossiers/{$dossier->id}", "/admin/dossiers/{$dossier->id}/edit",
            '/admin/quotes', '/admin/quotes/create', "/admin/quotes/{$quote->id}/edit",
            '/admin/invoices', '/admin/invoices/create', "/admin/invoices/{$invoice->id}/edit",
            '/admin/catalog-items', '/admin/settings',
        ] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get(route('admin.quotes.pdf', $quote))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('admin.invoices.pdf', $invoice))->assertOk();
    }
}
