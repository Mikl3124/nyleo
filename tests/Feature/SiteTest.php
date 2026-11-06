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
        CatalogItem::create(['category' => 'option', 'label' => 'COMPLÉMENT DE DOSSIER DEMANDÉ PAR LA MAIRIE', 'unit_price' => 0, 'position' => 9]);
        $this->get('/tarifs')->assertSeeInOrder(['COMPLÉMENT DE DOSSIER DEMANDÉ PAR LA MAIRIE', '0 €']);
        $this->get('/secteurs/lot')->assertNotFound();
        $this->get('/secteurs/dordogne/perigueux')->assertOk()->assertSee('à Périgueux')->assertSee('sud-ouest de Périgueux');
        $this->get('/secteurs/dordogne/une-commune-inconnue')->assertNotFound();
        $this->get('/secteurs/gironde/le-bouscat')->assertOk()->assertSee('au Bouscat')->assertDontSee('à Le Bouscat');
        $this->get('/secteurs/gironde/villenave-d-ornon')->assertSee("<title>Permis de construire et déclaration préalable à Villenave-d&#039;Ornon (33140)", false)->assertDontSee('&amp;#039;', false);
        $this->get('/secteurs/dordogne/sourzac')->assertDontSee('à 0 km')->assertDontSee('Sourzac, à');
        $this->get('/')->assertSee('"@context":"https://schema.org"', false)->assertDontSee('__contextArgs', false);
        $this->get('/questions-frequentes')->assertSee('"@context":"https://schema.org"', false);
        $this->get('/sitemap.xml')->assertSee(route('site.town', ['dordogne', 'bergerac']));
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee(route('site.pc'));
    }

    public function test_brand_is_always_written_with_a_capital_n(): void
    {
        \App\Models\CompanySetting::current()->update(['trade_name' => 'nyleo']);

        $this->get('/')->assertOk()->assertSee('<b>Nyleo</b>', false)->assertDontSee('<b>nyleo</b>', false);
        $this->get('/espace/connexion')->assertOk()->assertSee('<b>Nyleo</b>', false);
    }

    public function test_town_pages_use_the_public_data_of_their_commune(): void
    {
        // Périgueux : PLUi, monuments historiques, sites patrimoniaux remarquables, PPRI.
        $this->get('/secteurs/dordogne/perigueux')->assertOk()
            ->assertSee('plan local d&#039;urbanisme intercommunal (PLUi) de la communauté d&#039;agglomération Le Grand Périgueux', false)
            ->assertSee('Cathédrale Saint-Front')
            ->assertSee('site patrimonial remarquable')
            ->assertSee('PPRI AGGLOMERATION DE PERIGUEUX')
            ->assertSee('23 rue du Président-Wilson')
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('"@type":"Service"', false);
        // Sans PLU, le seuil des 40 m² ne s'applique pas.
        $this->get('/secteurs/gironde/hourtin')->assertOk()->assertSee('règlement national d&#039;urbanisme (RNU)', false)->assertSee('reste à 20 m²');
        $this->get('/secteurs/dordogne/eyraud-crempse-maurens')->assertSee('carte communale, approuvée en')->assertSee('Mairie d&#039;Eyraud', false);
        // Littoral, PPRT, feux de forêt.
        $this->get('/secteurs/gironde/la-teste-de-buch')->assertSee('submersion marine')->assertSee('risques technologiques')->assertSee('Feux de forêt');
        // Communes patrimoniales de moins de 1 000 habitants : leur propre page.
        $this->get('/secteurs/dordogne/domme')->assertOk()->assertSee('site patrimonial remarquable');
        $this->get('/secteurs/dordogne')->assertSee('PLU intercommunal')->assertSee('Sites patrimoniaux remarquables')->assertSee('"@type":"FAQPage"', false);
        $this->get('/sitemap.xml')->assertSee('<lastmod>', false)->assertSee(route('site.town', ['dordogne', 'domme']));
    }

    public function test_town_search_leads_to_the_town_page_or_to_the_quote_form(): void
    {
        $this->get('/secteurs/recherche?q=perigueux')->assertRedirect(route('site.town', ['dordogne', 'perigueux']));
        $this->get('/secteurs/recherche?q=SOURZAC')->assertRedirect(route('site.town', ['dordogne', 'sourzac']));
        $this->get('/secteurs/recherche?q=abjat sur bandiat')->assertRedirect(route('site.contact', ['commune' => 'Abjat-sur-Bandiat (24300)']));
        $this->get('/secteurs/recherche?q=st emilion')->assertRedirect(route('site.town', ['gironde', 'saint-emilion']));
        $this->get('/secteurs/recherche?q=st laurent des combes')->assertRedirect(route('site.contact', ['commune' => 'Saint-Laurent-des-Combes (33330)']));
        $this->get('/secteurs/recherche?q=xyz')->assertRedirect(route('site.areas'));
        $this->get('/secteurs/recherche?q=villenave d ornon')->assertRedirect(route('site.town', ['gironde', 'villenave-d-ornon']));
        $this->get('/secteurs/recherche?q=saint-jean-d-illac')->assertRedirect(route('site.town', ['gironde', 'saint-jean-d-illac']));
        // Même nom dans les deux départements : le département de la page ou le code postal tranchent.
        $this->get('/secteurs/recherche?q=Quinsac&dep=gironde')->assertRedirect(route('site.town', ['gironde', 'quinsac']));
        $this->get('/secteurs/recherche?q=Quinsac (24530)')->assertRedirect(route('site.contact', ['commune' => 'Quinsac (24530)']));
        $this->get('/secteurs/recherche?q=Quinsac')->assertRedirect(route('site.contact', ['commune' => 'Quinsac']));
        // Une commune de Dordogne cherchée depuis la page Gironde est quand même trouvée.
        $this->get('/secteurs/recherche?q=Ribérac&dep=gironde')->assertRedirect(route('site.town', ['dordogne', 'riberac']));
        $this->followingRedirects()->get('/secteurs/recherche?q=xyz')->assertSee('Commune introuvable');
        $this->get('/secteurs/recherche?q[]=a')->assertRedirect(route('site.areas'));
        $this->get('/contact?commune[]=a&demarche[]=DP')->assertOk();
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
