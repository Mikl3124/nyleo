<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/** Les écrans de connexion affichent des phrases en français, jamais des clés brutes (« passwords.user »). */
class AuthMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('fr');
        config(['app.fallback_locale' => 'fr']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_every_auth_and_password_message_is_translated(): void
    {
        foreach (['auth.failed', 'auth.throttle', 'passwords.reset', 'passwords.sent', 'passwords.throttled', 'passwords.token', 'passwords.user', 'validation.required', 'validation.email', 'pagination.next'] as $key) {
            $this->assertNotSame($key, __($key), $key);
        }
        $this->assertSame('Le champ adresse e-mail est obligatoire.', __('validation.required', ['attribute' => 'adresse e-mail']));
    }

    public function test_unknown_email_on_password_reset_says_so(): void
    {
        Notification::fake();

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => 'inconnu@example.com'])
            ->call('request')
            ->assertNotified(__('passwords.user'));

        $this->assertStringContainsString('Aucun compte', __('passwords.user'));
    }

    public function test_known_email_on_password_reset_confirms_sending(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'admin@example.com']);

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => 'admin@example.com'])
            ->call('request')
            ->assertNotified(__('passwords.sent'));
    }

    public function test_wrong_password_gives_a_clear_message(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        Livewire::test(Login::class)
            ->fillForm(['email' => 'admin@example.com', 'password' => 'mauvais'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertStringContainsString('Mot de passe oublié', __('filament-panels::auth/pages/login.messages.failed'));
    }
}
