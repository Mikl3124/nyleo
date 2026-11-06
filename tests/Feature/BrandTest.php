<?php

namespace Tests\Feature;

use App\Filament\NyleoAvatarProvider;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\PortalLink;
use App\Notifications\QuoteSent;
use App\Support\Brand;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Une seule charte : site, connexion, administration, espace client, e-mails, pages d'erreur. */
class BrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_and_password_reset_use_the_nyleo_identity(): void
    {
        foreach (['/admin/login', '/admin/password-reset/request'] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('css/admin-nyleo.css', false)
                ->assertSee('class="nyleo-brand"', false)
                ->assertSee('Administration')
                ->assertSee('Retour au site nyleo.fr')
                ->assertSee('favicon.svg', false);
        }
    }

    public function test_admin_dashboard_uses_the_nyleo_colours_logo_and_local_avatar(): void
    {
        $user = User::factory()->create(['name' => 'Mickaël Delpech']);

        $this->actingAs($user)->get('/admin')->assertOk()
            ->assertSee('css/admin-nyleo.css', false)
            ->assertSee('class="nyleo-brand"', false)
            ->assertDontSee('ui-avatars.com', false);

        $this->assertSame(Color::convertToOklch(Brand::BLUE), FilamentColor::getColor('primary')[600]);
        $this->assertSame(Color::convertToOklch(Brand::INK), FilamentColor::getColor('gray')[950]);
        $this->assertFalse(Filament::getPanel('admin')->hasDarkMode());

        $avatar = (new NyleoAvatarProvider)->get($user);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $avatar);
        $svg = base64_decode(substr($avatar, strlen('data:image/svg+xml;base64,')));
        $this->assertStringContainsString('>MD<', $svg);
        $this->assertStringContainsString(Brand::OCHRE, $svg);
    }

    public function test_client_login_highlights_the_main_action_in_ochre(): void
    {
        $this->get('/espace/connexion')->assertOk()
            ->assertSee('class="band"', false)
            ->assertSee('class="btn cta block"', false);
    }

    public function test_an_expired_login_link_explains_what_to_do(): void
    {
        $client = Client::create(['last_name' => 'Martin', 'email' => 'm@example.com']);
        $url = URL::temporarySignedRoute('portal.magic', now()->subMinute(), ['client' => $client->id]);

        $this->get($url)->assertForbidden()
            ->assertSee('Ce lien n’est plus valable')
            ->assertSee('Recevoir un nouveau lien')
            ->assertSee(url('/espace/connexion'), false)
            ->assertSee('css/nyleo.css', false)
            ->assertDontSee('Invalid signature');

        $this->get('/espace/acces/'.$client->id.'?signature=abc')->assertForbidden()->assertSee('Ce lien n’est plus valable');

        // Client déjà connecté : pas de nouveau lien à demander, on ouvre l'espace.
        $this->actingAs($client, 'client')->get($url)->assertForbidden()
            ->assertSee('Ouvrir mon espace')
            ->assertDontSee('Recevoir un nouveau lien');
    }

    public function test_a_broken_admin_password_reset_link_points_back_to_the_admin(): void
    {
        $this->get('/admin/password-reset/reset?email=demo%40nyleo.fr&token=abc&signature=bad')->assertForbidden()
            ->assertSee('Ce lien n’est plus valable')
            ->assertSee(url('/admin/password-reset/request'), false)
            ->assertDontSee('href="'.url('/espace/connexion').'">Recevoir', false);
    }

    public function test_error_pages_are_in_french_with_the_site_style(): void
    {
        $this->get('/une-page-qui-n-existe-pas')->assertNotFound()
            ->assertSee('Cette page n’existe pas')
            ->assertSee('class="logo"', false)
            ->assertSee('css/nyleo.css', false);

        foreach (['419' => 'Cette page a expiré', '429' => 'Trop d’essais', '500' => 'Un problème est survenu', '503' => 'Le site revient'] as $code => $text) {
            $this->assertStringContainsString($text, view('errors.'.$code)->render());
        }
        // « Réessayer » recharge la page demandée (lien de connexion compris), pas l'accueil.
        $this->assertStringContainsString('<a class="btn" href="">Réessayer</a>', view('errors.503')->render());
    }

    public function test_emails_carry_the_logo_the_ochre_button_and_the_contact_details(): void
    {
        CompanySetting::current()->update(['phone' => '07 85 65 91 65', 'city' => 'Sourzac', 'postal_code' => '24400', 'website' => 'https://nyleo.fr']);
        $client = Client::create(['last_name' => 'Martin', 'email' => 'm@example.com']);
        $quote = Quote::create(['client_id' => $client->id]);

        foreach ([(new PortalLink)->toMail($client), (new QuoteSent($quote))->toMail($client)] as $mail) {
            $html = (string) $mail->render();
            $this->assertStringContainsString('img/logo-nyleo-horizontal.png', $html);
            $this->assertStringContainsString('background-color: #e8a92b', $html);
            $this->assertStringContainsString('border-top: 4px solid #1d3f91', $html);
            $this->assertStringContainsString('Sourzac (24)', $html);
            $this->assertStringContainsString('07 85 65 91 65', $html);
            $this->assertStringNotContainsString('laravel.com', $html);
        }

        // Site saisi sans « https:// » dans les paramètres : les liens de l'e-mail restent valables.
        CompanySetting::current()->update(['website' => 'www.nyleo.fr']);
        $html = (string) (new PortalLink)->toMail($client)->render();
        $this->assertStringContainsString('href="https://www.nyleo.fr"', $html);
        $this->assertStringNotContainsString('href="www.nyleo.fr"', $html);
    }
}
