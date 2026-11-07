<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Dossier;
use App\Support\BotGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BotGuardTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            'last_name' => 'Martin', 'first_name' => 'Jeanne', 'email' => 'martin@example.org', 'phone' => '06 12 34 56 78',
            'procedure' => 'DP', 'nature' => 'pergola', 'site_city' => 'Bergerac (24100)', 'description' => 'Pergola au fond du jardin',
        ], BotGuard::fields(), $override);
    }

    private function assertNothingCreated(): void
    {
        $this->assertSame(0, Client::count());
        $this->assertSame(0, Dossier::count());
        Notification::assertNothingSent();
    }

    public function test_the_form_carries_the_invisible_fields_and_nothing_visible(): void
    {
        $html = $this->get(route('site.contact'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<input type="hidden" name="_t" value="[^"]{20,}">/', $html);
        $this->assertStringContainsString('<input type="hidden" name="_h" value="">', $html);
        $this->assertStringNotContainsStringIgnoringCase('captcha', $html);
    }

    public function test_a_human_request_goes_through(): void
    {
        Notification::fake();
        $this->post(route('site.request'), $this->payload())->assertRedirect(route('site.request.thanks'));
        $this->assertSame(1, Dossier::count());
    }

    /** @return array<string, array{0: array}> */
    public static function bots(): array
    {
        return [
            'pot de miel rempli' => [['website' => 'https://spam.example']],
            'sans jeton' => [['_t' => null, '_h' => null]],
            'jeton bricolé' => [['_t' => '1700000000', '_h' => '0000000071']],
            'envoi instantané' => [['fresh' => true]],
            'preuve fausse' => [['_h' => 'abc']],
            'lien dans le nom' => [['last_name' => 'Buy cheap https://spam.example']],
            'cyrillique' => [['description' => 'Здравствуйте, предлагаем продвижение сайта']],
            'liens en série' => [['description' => 'Voir https://a.example et https://b.example']],
        ];
    }

    #[DataProvider('bots')]
    public function test_bots_are_dropped_silently(array $override): void
    {
        Notification::fake();
        if ($override['fresh'] ?? false) { // formulaire rempli dans la seconde
            $override = ['_t' => $token = BotGuard::token(), '_h' => strrev($token)];
        }
        $this->post(route('site.request'), $this->payload($override))->assertRedirect(route('site.request.thanks'));
        $this->assertNothingCreated();
    }

    public function test_a_browser_without_javascript_gets_a_message_instead_of_silence(): void
    {
        Notification::fake();
        $this->from(route('site.contact'))->post(route('site.request'), $this->payload(['_h' => null]))
            ->assertRedirect(route('site.contact'))->assertSessionHasErrors('form');
        $this->assertNothingCreated();
    }

    public function test_a_single_link_in_the_description_is_accepted(): void
    {
        Notification::fake();
        $this->post(route('site.request'), $this->payload(['description' => 'Mon terrain : https://www.geoportail.gouv.fr/carte']))
            ->assertRedirect(route('site.request.thanks'));
        $this->assertSame(1, Dossier::count());
    }

    public function test_an_incomplete_phone_number_is_refused_with_a_message(): void
    {
        $this->post(route('site.request'), $this->payload(['phone' => '1234']))->assertSessionHasErrors('phone');
        $this->assertSame(0, Client::count());
    }

    private function withTurnstile(): void
    {
        config(['services.turnstile.site_key' => '0x4AAAAtest', 'services.turnstile.secret_key' => '0x4AAAAsecret']);
    }

    public function test_turnstile_widget_appears_only_when_keys_are_set(): void
    {
        $this->get(route('site.contact'))->assertDontSee('challenges.cloudflare.com', false);
        $this->withTurnstile();
        $this->get(route('site.contact'))->assertSee('data-sitekey="0x4AAAAtest"', false)
            ->assertSee('challenges.cloudflare.com/turnstile/v0/api.js', false);
    }

    public function test_turnstile_accepts_a_valid_token_and_drops_a_refused_one(): void
    {
        Notification::fake();
        $this->withTurnstile();
        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()
            ->push(['success' => true])->push(['success' => false, 'error-codes' => ['invalid-input-response']])]);

        $this->post(route('site.request'), $this->payload(['cf-turnstile-response' => 'ok']))->assertRedirect(route('site.request.thanks'));
        $this->assertSame(1, Dossier::count());

        $this->post(route('site.request'), $this->payload(['email' => 'robot@example.org', 'cf-turnstile-response' => 'faux']))
            ->assertRedirect(route('site.request.thanks'));
        $this->assertSame(1, Dossier::count());
        Http::assertSent(fn ($r) => $r['secret'] === '0x4AAAAsecret' && $r['response'] === 'ok');
    }

    public function test_turnstile_missing_token_warns_the_visitor_and_an_outage_lets_the_request_through(): void
    {
        Notification::fake();
        $this->withTurnstile();
        $this->from(route('site.contact'))->post(route('site.request'), $this->payload())->assertSessionHasErrors('form');
        $this->assertSame(0, Dossier::count());

        Http::fake(['challenges.cloudflare.com/*' => Http::response('', 503)]);
        $this->post(route('site.request'), $this->payload(['cf-turnstile-response' => 'ok']))->assertRedirect(route('site.request.thanks'));
        $this->assertSame(1, Dossier::count());
    }
}
