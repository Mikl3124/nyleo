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

        foreach (['/', '/declaration-prealable', '/permis-de-construire', '/tarifs', '/secteurs', '/secteurs/dordogne', '/secteurs/gironde', '/questions-frequentes', '/qui-suis-je', '/contact', '/secteurs/dordogne/sourzac', '/secteurs/gironde/bordeaux', '/mentions-legales', '/confidentialite'] as $url) {
            $this->get($url)->assertOk()->assertSee('Dordogne');
        }

        $this->get('/')->assertSee('400 €');
        $this->get('/tarifs')->assertSee('<li>DP1 : Plan de situation</li>', false);
        $this->get('/secteurs/lot')->assertNotFound();
        $this->get('/secteurs/dordogne/perigueux')->assertOk()->assertSee('à Périgueux')->assertSee('sud-ouest de Périgueux');
        $this->get('/secteurs/dordogne/une-commune-inconnue')->assertNotFound();
        $this->get('/sitemap.xml')->assertSee(route('site.town', ['dordogne', 'bergerac']));
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee(route('site.pc'));
    }

    public function test_town_search_leads_to_the_town_page_or_to_the_quote_form(): void
    {
        $this->get('/secteurs/recherche?q=perigueux')->assertRedirect(route('site.town', ['dordogne', 'perigueux']));
        $this->get('/secteurs/recherche?q=SOURZAC')->assertRedirect(route('site.town', ['dordogne', 'sourzac']));
        $this->get('/secteurs/recherche?q=abjat sur bandiat')->assertRedirect(route('site.contact', ['commune' => 'Abjat-sur-Bandiat (24300)']));
        $this->get('/secteurs/recherche?q=st emilion')->assertRedirect(route('site.contact', ['commune' => 'Saint-Émilion (33330)']));
        $this->get('/secteurs/recherche?q=xyz')->assertRedirect(route('site.areas'));
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
