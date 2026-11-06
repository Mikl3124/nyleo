{{-- Carte Dordogne + Gironde avec le bureau à Sourzac et des cercles de distance --}}
@php
    $g = \App\Support\Geo::class;
    $region = $g::region();
    $base = $g::base();
    $labelled = ['Périgueux', 'Bergerac', 'Sarlat-la-Canéda', 'Ribérac', 'Nontron', 'Bordeaux', 'Libourne', 'Arcachon', 'Langon', 'Lesparre-Médoc', 'Blaye'];
    $towns = $g::pages();
@endphp
<svg class="map" viewBox="{{ $region['viewBox'] }}" role="img" aria-labelledby="map-r-t map-r-d">
    <title id="map-r-t">Carte de la Dordogne et de la Gironde</title>
    <desc id="map-r-d">Le bureau est à {{ $base['name'] }}, en Dordogne. Cercles de 25, 50 et 100 km autour. Chaque point est une commune avec sa page.</desc>
    @foreach ($region['neighbours'] as $p)<path class="neigh" d="{{ $p }}"/>@endforeach
    <a href="{{ route('site.area', 'dordogne') }}" aria-label="Dordogne"><path class="dep" d="{{ $g::department('dordogne')['outline'] }}"/></a>
    <a href="{{ route('site.area', 'gironde') }}" aria-label="Gironde"><path class="dep alt" d="{{ $g::department('gironde')['outline'] }}"/></a>
    <text class="dep-label" x="62" y="40" text-anchor="middle">Dordogne</text>
    <text class="dep-label" x="-62" y="66" text-anchor="middle">Gironde</text>
    @foreach ([25, 50, 100] as $r)
        <circle class="ring" cx="{{ $base['x'] }}" cy="{{ $base['y'] }}" r="{{ $r }}"/>
        <text class="ring-label" x="{{ $base['x'] + 2 }}" y="{{ $base['y'] - $r - 1.5 }}">{{ $r }} km</text>
    @endforeach
    @foreach ($towns as $t)
        <a href="{{ $g::url($t) }}" aria-label="{{ $t['name'] }}">
            <circle class="town" cx="{{ $t['x'] }}" cy="{{ $t['y'] }}" r="{{ in_array($t['name'], $labelled, true) ? 2.1 : 1.15 }}"/>
        </a>
    @endforeach
    @foreach ($towns->filter(fn ($t) => in_array($t['name'], $labelled, true)) as $t)
        <text class="town-label" x="{{ $t['x'] > 75 ? $t['x'] - 3.2 : $t['x'] + 3.2 }}" y="{{ $t['y'] + 1.8 }}" text-anchor="{{ $t['x'] > 75 ? 'end' : 'start' }}">{{ $t['name'] }}</text>
    @endforeach
    <circle class="base-pulse" cx="{{ $base['x'] }}" cy="{{ $base['y'] }}" r="5"/>
    <circle class="base-dot" cx="{{ $base['x'] }}" cy="{{ $base['y'] }}" r="3.2"/>
    <text class="town-label" x="{{ $base['x'] - 3 }}" y="{{ $base['y'] + 10 }}" text-anchor="middle" style="font-size:6px">{{ $base['name'] }} · le bureau</text>
</svg>
