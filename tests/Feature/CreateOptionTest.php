<?php

namespace Tests\Feature;

use App\Filament\Resources\Dossiers\Pages\CreateDossier;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Quotes\Pages\CreateQuote;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateOptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_be_created_from_the_quote_invoice_and_dossier_forms(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ([CreateQuote::class, CreateInvoice::class, CreateDossier::class] as $i => $page) {
            Livewire::test($page)
                ->mountFormComponentAction('client_id', 'createOption')
                ->assertHasNoFormComponentActionErrors()
                ->setFormComponentActionData(['last_name' => 'Durand'.$i, 'type' => 'particulier'])
                ->callMountedFormComponentAction()
                ->assertHasNoFormComponentActionErrors()
                // Le client créé est aussitôt choisi dans la liste.
                ->assertSet('data.client_id', Client::where('last_name', 'Durand'.$i)->value('id'));
        }
        $this->assertSame(3, Client::where('last_name', 'like', 'Durand%')->count());
    }
}
