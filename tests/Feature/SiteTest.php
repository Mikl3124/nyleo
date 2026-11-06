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

        foreach (['/', '/declaration-prealable', '/permis-de-construire', '/secteurs', '/permis-de-construire/dordogne', '/permis-de-construire/gironde', '/questions-frequentes', '/qui-suis-je', '/contact', '/permis-de-construire/dordogne/sourzac', '/permis-de-construire/gironde/bordeaux', '/mentions-legales', '/confidentialite', '/cgv'] as $url) {
            $this->get($url)->assertOk()->assertSee('Dordogne');
        }

        $this->get('/tarifs')->assertRedirect('/contact')->assertStatus(301);
        $this->get('/permis-de-construire/landes')->assertNotFound();
        $this->get('/permis-de-construire/lot')->assertOk()->assertSee('dans le Lot');
        $this->get('/permis-de-construire/dordogne/perigueux')->assertOk()->assertSee('à Périgueux')->assertSee('sud-ouest de Périgueux');
        $this->get('/permis-de-construire/dordogne/une-commune-inconnue')->assertNotFound();
        $this->get('/permis-de-construire/gironde/le-bouscat')->assertOk()->assertSee('au Bouscat')->assertDontSee('à Le Bouscat');
        $this->get('/permis-de-construire/gironde/villenave-d-ornon')->assertSee("<title>Permis de construire à Villenave-d&#039;Ornon (33140)", false)->assertDontSee('&amp;#039;', false);
        $this->get('/permis-de-construire/dordogne/sourzac')->assertDontSee('à 0 km')->assertDontSee('Sourzac, à');
        $this->get('/')->assertSee('"@context":"https://schema.org"', false)->assertDontSee('__contextArgs', false);
        $this->get('/questions-frequentes')->assertSee('"@context":"https://schema.org"', false);
        $this->get('/sitemap.xml')->assertSee(route('site.pc.town', ['dordogne', 'bergerac']));
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee(route('site.pc'));
    }

    public function test_project_guides_render_and_are_linked_everywhere(): void
    {
        CatalogItem::create(['category' => 'DP', 'label' => 'DP véranda', 'unit_price' => 400, 'position' => 1]);
        CatalogItem::create(['category' => 'PC', 'label' => 'PC maison', 'unit_price' => 1500, 'position' => 2]);

        $this->get('/projets')->assertOk()->assertSee('Piscine')->assertSee(route('site.project', 'changement-de-destination'));
        foreach (array_keys(\App\Support\ProjectGuides::GUIDES) as $slug) {
            $this->get('/projets/'.$slug)->assertOk()
                ->assertSee('"@type":"FAQPage"', false)
                ->assertSee('"@type":"BreadcrumbList"', false)
                ->assertSee('"@type":"Service"', false)
                ->assertDontSee('architecte</b> Mickaël', false);
        }
        $this->get('/projets/veranda')->assertSee("<title>Véranda\u{00A0}: déclaration préalable ou permis de construire\u{00A0}? | Nyleo</title>", false)
            ->assertSee('value="extension" checked', false);
        $this->get('/projets/piscine')->assertSee('value="piscine" checked', false)->assertSee('?demarche=DP', false);
        $this->get('/projets/une-idee')->assertNotFound();

        // Maillage : accueil, pages villes, pied de page et plan du site mènent aux guides.
        $this->get('/')->assertSee(route('site.project', 'veranda'));
        $this->get('/permis-de-construire/dordogne/perigueux')->assertSee(route('site.project', 'piscine'));
        $this->get('/mentions-legales')->assertSee(route('site.project', 'cloture-portail'));
        $this->get('/sitemap.xml')->assertSee(route('site.projects'))->assertSee(route('site.project', 'panneaux-solaires'));
    }

    public function test_site_wide_structured_data_and_light_pages(): void
    {
        $home = $this->get('/')->assertOk();
        $home->assertSee('"@type":"WebSite"', false)->assertSee('"@type":"ProfessionalService"', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<meta property="og:image:width" content="1200">', false);
        // La liste des 1 000 communes n'est plus dans la page : elle est chargée à la demande.
        $home->assertDontSee('<option value="Abjat-sur-Bandiat (24300)">', false)->assertSee(route('site.communes'));
        $this->assertLessThan(170000, strlen($home->getContent()));
        $this->getJson('/communes.json')->assertOk()->assertJsonFragment(['Sourzac (24400)'])->assertHeader('X-Robots-Tag', 'noindex');
        $this->get('/qui-suis-je')->assertSee('"@type":"Person"', false);
        $this->get('/permis-de-construire')->assertSee('"@type":"Service"', false);
    }

    public function test_public_site_shows_no_price(): void
    {
        CatalogItem::create(['category' => 'DP', 'label' => 'DP véranda', 'unit_price' => 400, 'position' => 1]);
        CatalogItem::create(['category' => 'PC', 'label' => 'PC maison', 'unit_price' => 1500, 'position' => 2]);
        $urls = ['/cgv', '/', '/declaration-prealable', '/permis-de-construire', '/projets', '/secteurs', '/permis-de-construire/dordogne', '/questions-frequentes', '/qui-suis-je', '/contact', '/permis-de-construire/dordogne/perigueux', '/permis-de-construire/gironde/bordeaux'];
        $urls = array_merge($urls, array_map(fn ($s) => '/projets/'.$s, array_keys(\App\Support\ProjectGuides::GUIDES)));
        foreach ($urls as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $text = mb_strtolower(strip_tags(preg_replace('#<(script|style)\b.*?</\1>#s', '', $html)));
            $this->assertStringNotContainsString('€', $html, $url);
            $this->assertDoesNotMatchRegularExpression('/\bprix\b|\btarifs?\b|à partir de/u', $text, $url);
            $this->assertStringNotContainsString('price', $html, $url); // ni minPrice, ni priceRange dans les données structurées
            $this->assertStringNotContainsString('/tarifs', $html, $url);
        }
        // Plus de zone photo à remplir dans « Qui suis-je » ni sur l'accueil.
        $this->get('/qui-suis-je')->assertDontSee('class="portrait"', false);
        $this->get('/')->assertDontSee('class="portrait"', false);
        $this->get('/sitemap.xml')->assertDontSee('/tarifs');
    }

    public function test_neighbouring_departments_have_their_pages(): void
    {
        foreach (['charente' => ['Charente', 'angouleme'], 'charente-maritime' => ['Charente-Maritime', 'la-rochelle'], 'haute-vienne' => ['Haute-Vienne', 'limoges'], 'correze' => ['Corrèze', 'brive-la-gaillarde'], 'lot' => ['Lot', 'cahors'], 'lot-et-garonne' => ['Lot-et-Garonne', 'agen']] as $slug => [$name, $town]) {
            $this->get('/permis-de-construire/'.$slug)->assertOk()->assertSee($name)->assertSee('"@type":"FAQPage"', false);
            $this->get('/permis-de-construire/'.$slug.'/'.$town)->assertOk()->assertSee('"@type":"Service"', false)->assertDontSee('€');
        }
        $this->get('/permis-de-construire/lot')->assertSee('Permis de construire <span class="hl">dans le Lot</span>', false)->assertSee('Intervenez-vous dans tout le Lot');
        $this->get('/secteurs/recherche?q=Brive')->assertRedirect(route('site.pc.town', ['correze', 'brive-la-gaillarde']));
        $sitemap = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertGreaterThan(700, substr_count($sitemap, '<loc>'));
        $this->assertStringContainsString(route('site.pc.area', 'haute-vienne'), $sitemap);
        // La carte du rayon d'action montre les huit départements, et chacun mène à sa page.
        $home = $this->get('/')->assertSee('Carte du rayon d', false)->assertDontSee('communes avec leur page')->assertDontSee('Cercles :')->assertDontSee('class="ring"', false)->getContent();
        foreach (\App\Support\Geo::departmentSlugs() as $d) {
            $this->assertStringContainsString('href="'.route('site.pc.area', $d).'"', $home);
        }
        $this->getJson('/communes.json?dep=lot')->assertOk()->assertJsonFragment(['Cahors (46000)'])->assertJsonMissing(['Sourzac (24400)']);
    }

    public function test_each_town_and_department_has_one_page_per_procedure(): void
    {
        // Déclaration préalable et permis de construire : deux pages distinctes, chacune avec son titre et son contenu.
        $dp = $this->get('/declaration-prealable/dordogne/bergerac')->assertOk()
            ->assertSee('<title>Déclaration préalable de travaux à Bergerac', false)
            ->assertSee('Déclaration préalable ou pas')->assertSee('CERFA 13703')
            ->assertSee('href="'.route('site.pc.town', ['dordogne', 'bergerac']).'"', false)
            ->assertDontSee('Combien de logements sont autorisés')->getContent();
        $pc = $this->get('/permis-de-construire/dordogne/bergerac')->assertOk()
            ->assertSee('<title>Permis de construire à Bergerac', false)
            ->assertSee('Ce que le terrain impose')->assertSee('CERFA 13406')->assertSee('Combien de logements sont autorisés')
            ->assertSee('href="'.route('site.dp.town', ['dordogne', 'bergerac']).'"', false)
            ->assertDontSee('Déclaration préalable ou pas')->getContent();
        $this->assertStringNotContainsString('€', $dp.$pc);

        $this->get('/declaration-prealable/gironde')->assertOk()->assertSee('<title>Déclaration préalable de travaux en Gironde (33)', false)->assertSee('"@type":"FAQPage"', false)
            ->assertSee('href="'.route('site.dp.town', ['gironde', 'bordeaux']).'"', false);
        $this->get('/permis-de-construire/gironde')->assertOk()->assertSee('<title>Permis de construire en Gironde (33)', false)
            ->assertSee('href="'.route('site.pc.town', ['gironde', 'bordeaux']).'"', false);
        $this->get('/declaration-prealable/dordogne/une-commune-inconnue')->assertNotFound();
        $this->get('/declaration-prealable/landes')->assertNotFound();

        // Anciennes adresses : redirection permanente, sans page en double.
        $this->get('/secteurs/dordogne/bergerac')->assertStatus(301)->assertRedirect(route('site.pc.town', ['dordogne', 'bergerac']));
        $this->get('/secteurs/lot')->assertStatus(301)->assertRedirect(route('site.pc.area', 'lot'));
        $this->get('/secteurs/dordogne/une-commune-inconnue')->assertNotFound();
        $this->get('/secteurs')->assertOk();

        // La recherche mène à la page de la démarche choisie.
        $this->get('/secteurs/recherche?q=Bergerac&type=dp')->assertRedirect(route('site.dp.town', ['dordogne', 'bergerac']));

        $sitemap = $this->get('/sitemap.xml')->getContent();
        $this->assertStringContainsString(route('site.dp.town', ['dordogne', 'bergerac']), $sitemap);
        $this->assertStringContainsString(route('site.pc.town', ['dordogne', 'bergerac']), $sitemap);
        $this->assertStringNotContainsString('/secteurs/dordogne', $sitemap);
        $this->assertSame(count(array_unique(\Illuminate\Support\Str::matchAll('/<loc>([^<]+)<\/loc>/', $sitemap)->all())), substr_count($sitemap, '<loc>'));
    }

    public function test_cgv_page_is_published_and_linked_everywhere(): void
    {
        $this->get('/cgv')->assertOk()
            ->assertSee('Conditions générales de vente')
            ->assertSee('droit de rétractation')
            ->assertSee('CERFA')
            ->assertSee(\App\Support\Legal::MEDIATOR_URL);

        // Pied de page du site et de l'espace client, plan du site.
        $this->get('/')->assertSee('href="'.route('cgv').'"', false);
        $this->get('/espace/connexion')->assertSee('href="'.route('cgv').'"', false);
        $this->get('/sitemap.xml')->assertSee(route('cgv'));

        // Lien par défaut sur les devis, remplaçable dans Paramètres.
        $company = \App\Models\CompanySetting::current();
        $this->assertSame(route('cgv'), $company->cgvUrl());
        $this->assertSame(route('cgv'), $company->snapshot()['cgv_url']);
        $company->update(['cgv_url' => 'https://exemple.fr/cgv']);
        $this->assertSame('https://exemple.fr/cgv', $company->fresh()->cgvUrl());
        $this->get('/')->assertSee('href="https://exemple.fr/cgv"', false);
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
        $this->get('/permis-de-construire/dordogne/perigueux')->assertOk()
            ->assertSee('plan local d&#039;urbanisme intercommunal (PLUi) de la communauté d&#039;agglomération Le Grand Périgueux', false)
            ->assertSee('site patrimonial remarquable')
            ->assertSee('PPRI AGGLOMERATION DE PERIGUEUX')
            ->assertSee('23 rue du Président-Wilson')
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('"@type":"Service"', false);
        $this->get('/declaration-prealable/dordogne/perigueux')->assertOk()->assertSee('Cathédrale Saint-Front')->assertSee("Un dossier qui passe chez l'ABF", false);
        // Sans PLU, le seuil des 40 m² ne s'applique pas.
        $this->get('/permis-de-construire/gironde/hourtin')->assertOk()->assertSee('règlement national d&#039;urbanisme (RNU)', false)->assertSee('reste à 20');
        $this->get('/permis-de-construire/dordogne/eyraud-crempse-maurens')->assertSee('carte communale, approuvée en')->assertSee('Mairie d&#039;Eyraud', false);
        // Littoral, PPRT, feux de forêt.
        $this->get('/permis-de-construire/gironde/la-teste-de-buch')->assertSee('submersion marine')->assertSee('risques technologiques')->assertSee('Feux de forêt');
        // Communes patrimoniales de moins de 1 000 habitants : leur propre page.
        $this->get('/permis-de-construire/dordogne/domme')->assertOk()->assertSee('site patrimonial remarquable');
        $this->get('/permis-de-construire/dordogne')->assertSee('PLU intercommunal');
        $this->get('/declaration-prealable/dordogne')->assertSee('Sites patrimoniaux remarquables')->assertSee('"@type":"FAQPage"', false);
        $this->get('/sitemap.xml')->assertSee('<lastmod>', false)->assertSee(route('site.pc.town', ['dordogne', 'domme']));
    }

    public function test_town_search_leads_to_the_town_page_or_to_the_quote_form(): void
    {
        $this->get('/secteurs/recherche?q=perigueux')->assertRedirect(route('site.pc.town', ['dordogne', 'perigueux']));
        $this->get('/secteurs/recherche?q=SOURZAC')->assertRedirect(route('site.pc.town', ['dordogne', 'sourzac']));
        $this->get('/secteurs/recherche?q=abjat sur bandiat')->assertRedirect(route('site.contact', ['commune' => 'Abjat-sur-Bandiat (24300)']));
        $this->get('/secteurs/recherche?q=st emilion')->assertRedirect(route('site.pc.town', ['gironde', 'saint-emilion']));
        $this->get('/secteurs/recherche?q=st laurent des combes&dep=gironde')->assertRedirect(route('site.contact', ['commune' => 'Saint-Laurent-des-Combes (33330)']));
        $this->get('/secteurs/recherche?q=xyz')->assertRedirect(route('site.areas'));
        $this->get('/secteurs/recherche?q=villenave d ornon')->assertRedirect(route('site.pc.town', ['gironde', 'villenave-d-ornon']));
        $this->get('/secteurs/recherche?q=saint-jean-d-illac')->assertRedirect(route('site.pc.town', ['gironde', 'saint-jean-d-illac']));
        // Même nom dans les deux départements : le département de la page ou le code postal tranchent.
        $this->get('/secteurs/recherche?q=Quinsac&dep=gironde')->assertRedirect(route('site.pc.town', ['gironde', 'quinsac']));
        $this->get('/secteurs/recherche?q=Quinsac (24530)')->assertRedirect(route('site.contact', ['commune' => 'Quinsac (24530)']));
        $this->get('/secteurs/recherche?q=Quinsac')->assertRedirect(route('site.contact', ['commune' => 'Quinsac']));
        // Une commune de Dordogne cherchée depuis la page Gironde est quand même trouvée.
        $this->get('/secteurs/recherche?q=Ribérac&dep=gironde')->assertRedirect(route('site.pc.town', ['dordogne', 'riberac']));
        $this->followingRedirects()->get('/secteurs/recherche?q=xyz')->assertSee('Commune introuvable');
        $this->get('/secteurs/recherche?q[]=a')->assertRedirect(route('site.areas'));
        $this->get('/contact?commune[]=a&demarche[]=DP')->assertOk();
    }

    public function test_request_form_leads_to_a_confirmation_screen_with_a_recap(): void
    {
        $this->from(route('site.contact'))->post(route('site.request'), [
            'last_name' => 'Martin', 'first_name' => 'Jeanne', 'email' => 'martin@example.org', 'phone' => '06 00 00 00 00',
            'procedure' => 'DP', 'nature' => 'pergola', 'site_city' => 'Bergerac (24100)', 'description' => 'Pergola au fond du jardin',
        ])->assertRedirect(route('site.request.thanks'));

        $this->assertDatabaseHas('dossiers', ['site_city' => 'Bergerac', 'procedure' => 'DP']);

        $html = $this->get(route('site.request.thanks'))->assertOk()
            ->assertSee('Merci Jeanne, votre demande est bien reçue')
            ->assertSee('Pergola, carport, abri')->assertSee('Déclaration préalable')
            ->assertSee('Bergerac (24100)')->assertSee('martin@example.org')->assertSee('Pergola au fond du jardin')
            ->assertSee(route('site.project', 'abri-de-jardin-carport-pergola'))
            ->assertSee('<meta name="robots" content="noindex">', false)
            ->assertDontSee('id="demande"', false)
            ->getContent();

        $this->assertStringContainsString("48\u{00A0}h ouvrées", $html); // « 48 » et « h » ne se séparent jamais
        $this->assertStringNotContainsString('€', $html);

        // Rechargement : le récapitulatif reste affiché.
        $this->get(route('site.request.thanks'))->assertOk()->assertSee('Bergerac (24100)');
    }

    public function test_confirmation_screen_without_a_request_goes_back_to_the_form(): void
    {
        $this->get(route('site.request.thanks'))->assertRedirect(route('site.contact'));
    }
}
