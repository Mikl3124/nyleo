<?php

namespace Tests\Unit;

use App\Services\Communes;
use Tests\TestCase;

class CommunesTest extends TestCase
{
    public function test_search_finds_communes_with_departement(): void
    {
        $first = Communes::search('perigu')[0];
        $this->assertSame('Périgueux', $first['nom']);
        $this->assertSame('24', $first['departement']);
        $this->assertSame('Périgueux (24 - Dordogne)', $first['label']);
    }

    public function test_search_ignores_accents_and_abbreviations(): void
    {
        $labels = array_column(Communes::search('st emilion'), 'label');
        $this->assertContains('Saint-Émilion (33 - Gironde)', $labels);
        $this->assertSame('Bordeaux', Communes::search('BORDEAUX')[0]['nom']);
    }

    public function test_short_or_unknown_query_returns_nothing(): void
    {
        $this->assertSame([], Communes::search('a'));
        $this->assertSame([], Communes::search('zzzzqqq'));
    }
}
