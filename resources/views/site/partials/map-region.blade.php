{{-- Carte du rayon d'action : la Dordogne au centre, ses départements voisins, le bureau et les grandes villes --}}
@php
    $g = \App\Support\Geo::class;
    $region = $g::region();
    $base = $g::base();
    $deps = collect($g::departmentSlugs())->map(fn ($s) => $g::department($s));
    // Nom de chaque département, placé à la main là où il ne touche ni une ville ni une frontière.
    $labelAt = ['dordogne' => [79, -41], 'gironde' => [-16, 39], 'charente' => [21, -96], 'charente-maritime' => [-65, -61], 'haute-vienne' => [130, -110], 'correze' => [159, -45], 'lot' => [141, 36], 'lot-et-garonne' => [23, 80]];
    $short = ['charente-maritime' => 'Charente-Mar.', 'lot-et-garonne' => 'Lot-et-Gar.'];
    $labelled = ['Périgueux', 'Bergerac', 'Sarlat-la-Canéda', 'Bordeaux', 'Libourne', 'Arcachon', 'Angoulême', 'Cognac', 'La Rochelle', 'Saintes', 'Limoges', 'Brive-la-Gaillarde', 'Tulle', 'Cahors', 'Figeac', 'Agen', 'Villeneuve-sur-Lot', 'Marmande', 'Ribérac', 'Nontron'];
    $towns = $g::pages();
@endphp
<svg class="map map-region" viewBox="{{ $region['viewBox'] }}" role="img" aria-labelledby="map-r-t map-r-d">
    <title id="map-r-t">Carte du rayon d'action : la Dordogne et ses départements voisins</title>
    <desc id="map-r-d">Le bureau est à {{ $base['name'] }}, en Dordogne. J'interviens {{ $g::inDep('dordogne') }} et dans les départements voisins : {{ $deps->pluck('name')->except(0)->implode(', ') }}.</desc>
    @foreach ($region['neighbours'] as $p)<path class="neigh" d="{{ $p }}"/>@endforeach
    @foreach ($deps as $d)
        <a href="{{ $g::areaUrl($d['slug']) }}" aria-label="{{ $d['name'] }}"><path class="dep{{ $d['slug'] === 'dordogne' ? ' home' : ' alt' }}" d="{{ $region['outlines'][$d['slug']] ?? $d['outline'] }}"/></a>
    @endforeach
    @foreach ($deps as $d)
        @php([$lx, $ly] = $labelAt[$d['slug']] ?? [$g::communes($d['slug'])->avg('x'), $g::communes($d['slug'])->avg('y')])
        <text class="dep-label" x="{{ $lx }}" y="{{ $ly }}" text-anchor="middle">{{ $short[$d['slug']] ?? $d['name'] }}</text>
    @endforeach
    @foreach ($towns->filter(fn ($t) => in_array($t['name'], $labelled, true)) as $t)
        <a href="{{ parse_url($g::url($t), PHP_URL_PATH) }}" tabindex="-1" aria-hidden="true"><circle class="town" cx="{{ $t['x'] }}" cy="{{ $t['y'] }}" r="2.3"/></a>
    @endforeach
    @foreach ($towns->filter(fn ($t) => in_array($t['name'], $labelled, true)) as $t)
        @php($left = in_array($t['name'], ['Arcachon', 'La Rochelle', 'Cognac', 'Marmande', 'Libourne', 'Ribérac'], true))
        <text class="town-label" x="{{ $left ? $t['x'] - 3.6 : $t['x'] + 3.6 }}" y="{{ $t['y'] + 2.2 }}" text-anchor="{{ $left ? 'end' : 'start' }}">{{ $t['name'] }}</text>
    @endforeach
    <circle class="base-pulse" cx="{{ $base['x'] }}" cy="{{ $base['y'] }}" r="7"/>
    <circle class="base-dot" cx="{{ $base['x'] }}" cy="{{ $base['y'] }}" r="4.2"/>
    <text class="town-label base-label" x="{{ $base['x'] }}" y="{{ $base['y'] + 12 }}" text-anchor="middle">{{ $base['name'] }} · le bureau</text>
</svg>
