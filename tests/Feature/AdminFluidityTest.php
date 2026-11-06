<?php

namespace Tests\Feature;

use App\Enums\DocumentCategory;
use App\Enums\DossierStatus;
use App\Filament\Resources\Dossiers\Pages\ViewDossier;
use App\Filament\Widgets\TodoDossiers;
use App\Filament\Widgets\UnpaidInvoices;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\DossierUpdate;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminFluidityTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_widgets_list_what_needs_doing(): void
    {
        Notification::fake();
        $this->seed();
        $this->actingAs(User::factory()->create());
        $client = Client::create(['last_name' => 'Martin', 'email' => 'm@example.com', 'phone' => '06 12 34 56 78']);
        $new = Dossier::create(['client_id' => $client->id, 'title' => 'Piscine Saint-Astier', 'procedure' => 'DP']);
        $waiting = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda attente', 'procedure' => 'DP', 'status' => DossierStatus::PlansAValider]);

        $this->get('/admin')->assertOk()->assertSee('Nouveau dossier');
        Livewire::test(TodoDossiers::class)
            ->assertCanSeeTableRecords([$new])
            ->assertCanNotSeeTableRecords([$waiting])
            ->assertSee('Rappeler le client et faire le devis');
        $this->assertFalse(UnpaidInvoices::canView()); // rien à encaisser : le bloc est masqué

        $quote = Quote::create(['client_id' => $client->id, 'dossier_id' => $new->id]);
        $quote->lines()->create(['label' => 'DP', 'quantity' => 1, 'unit_price' => 400]);
        $quote->recalculate();
        $invoice = InvoiceService::issue(InvoiceService::createFinalFromQuote($quote), notify: false);
        $this->assertTrue(UnpaidInvoices::canView());
        Livewire::test(UnpaidInvoices::class)
            ->assertCanSeeTableRecords([$invoice])
            ->callTableAction('payment', $invoice, ['amount' => 400, 'method' => 'virement', 'paid_at' => now()->toDateString()])
            ->assertHasNoTableActionErrors();
        $this->assertSame('payee', $invoice->refresh()->status->value);
    }

    public function test_plans_are_uploaded_and_sent_for_validation_in_one_step(): void
    {
        Notification::fake();
        Storage::fake('local');
        $this->seed();
        $this->actingAs(User::factory()->create());
        $client = Client::create(['last_name' => 'Martin', 'email' => 'm@example.com']);
        $dossier = Dossier::create(['client_id' => $client->id, 'title' => 'Véranda', 'procedure' => 'DP', 'nature' => 'veranda', 'status' => DossierStatus::PlansEnCours]);

        Livewire::test(ViewDossier::class, ['record' => $dossier->getRouteKey()])
            ->callAction('sendPlans', [
                'file' => UploadedFile::fake()->create('plans.pdf', 200, 'application/pdf'),
                'name' => 'Plans DP véranda',
                'message' => 'Bonne lecture',
            ])
            ->assertHasNoActionErrors();

        $dossier->refresh();
        $plan = $dossier->documents()->first();
        $this->assertSame(DossierStatus::PlansAValider, $dossier->status);
        $this->assertSame(DocumentCategory::Plan, $plan->category);
        $this->assertTrue($plan->isAwaitingValidation());
        $this->assertTrue($plan->visible_to_client);
        Notification::assertSentTo($client, DossierUpdate::class);
    }
}
