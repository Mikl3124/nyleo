<?php

namespace Tests\Feature;

use App\Filament\Resources\Quotes\Pages\CreateQuote;
use App\Filament\Resources\Dossiers\Pages\CreateDossier;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateOptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_be_created_from_the_quote_and_dossier_forms(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ([CreateQuote::class, CreateDossier::class] as $page) {
            Livewire::test($page)
                ->mountFormComponentAction('client_id', 'createOption')
                ->assertHasNoFormComponentActionErrors()
                ->setFormComponentActionData(['last_name' => 'Durand', 'type' => 'particulier'])
                ->callMountedFormComponentAction()
                ->assertHasNoFormComponentActionErrors();
        }
        $this->assertDatabaseHas('clients', ['last_name' => 'Durand']);
    }
}
