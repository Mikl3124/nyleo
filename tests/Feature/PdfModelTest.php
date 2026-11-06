<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Support\DefaultCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_description_dashes_become_bullets_and_colon_lines_become_subtitles(): void
    {
        $html = rich_description("Dossier complet.\nContenu du dossier :\n- DP1 : Plan de situation\n- DP2 : Plan de masse\nFin.");

        $this->assertStringContainsString('<div class="subtitle">Contenu du dossier :</div>', $html);
        $this->assertStringContainsString('<ul><li>DP1 : Plan de situation</li><li>DP2 : Plan de masse</li></ul>', $html);
        $this->assertStringContainsString('<div>Fin.</div>', $html);
        $this->assertStringNotContainsString('<script>', rich_description('<script>x</script>'));
    }

    public function test_default_catalog_is_created_once_and_never_overwritten(): void
    {
        DefaultCatalog::seed();
        $this->assertSame(count(DefaultCatalog::items()), CatalogItem::count());

        CatalogItem::first()->update(['description' => 'Texte modifié par Mickaël']);
        DefaultCatalog::seed();

        $this->assertSame(count(DefaultCatalog::items()), CatalogItem::count());
        $this->assertSame('Texte modifié par Mickaël', CatalogItem::first()->description);
    }
}
