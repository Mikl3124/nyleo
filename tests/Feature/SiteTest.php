<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_public_page_renders(): void
    {
        CatalogItem::create(['category' => 'DP', 'label' => 'DP véranda', 'description' => "Intro\nContenu du dossier :\n- DP1 : Plan de situation", 'unit_price' => 400, 'position' => 1]);

        foreach (['/', '/declaration-prealable', '/permis-de-construire', '/tarifs', '/secteurs/dordogne', '/secteurs/gironde', '/questions-frequentes', '/contact', '/mentions-legales', '/confidentialite'] as $url) {
            $this->get($url)->assertOk()->assertSee('Dordogne');
        }

        $this->get('/')->assertSee('400 €');
        $this->get('/tarifs')->assertSee('<li>DP1 : Plan de situation</li>', false);
        $this->get('/secteurs/lot')->assertNotFound();
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee(route('site.pc'));
    }

    public function test_request_form_redirects_back_to_the_page_it_came_from(): void
    {
        $this->from(route('site.contact'))->post(route('site.request'), [
            'last_name' => 'Martin', 'email' => 'martin@example.org', 'phone' => '0600000000',
            'procedure' => 'DP', 'nature' => 'pergola', 'site_city' => 'Bergerac',
        ])->assertRedirect(route('site.contact').'#demande');

        $this->assertDatabaseHas('dossiers', ['site_city' => 'Bergerac', 'procedure' => 'DP']);
    }
}
