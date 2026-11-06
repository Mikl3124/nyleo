{{-- Carte d'un département : toutes les communes ; celles qui ont une page sont cliquables --}}
@php
    $g = \App\Support\Geo::class;
    $dep = $g::department($slug);
    $communes = $g::communes($slug);
    $base = $g::base();
    [$vx, , $vw] = array_map('floatval', explode(' ', $dep['viewBox']));
    $labels = $g::spread($g::pages($slug)->reject(fn ($c) => $c['code'] === $base['code'])->take(30), 16, $base['dep'] === $slug ? $base : null)->take(8);
@endphp
<svg class="map" viewBox="{{ $dep['viewBox'] }}" role="img" aria-labelledby="map-d-t">
    <title id="map-d-t">Carte des communes de {{ $dep['name'] }}</title>
    <path class="dep" d="{{ $dep['outline'] }}"/>
    <g class="mesh" aria-hidden="true">
        @foreach ($communes as $c)
            @if ($c['page'])
                <a href="{{ $g::url($c, $kind ?? 'pc') }}" tabindex="-1"><title>{{ $c['name'] }}</title><path d="{{ $c['path'] }}"/></a>
            @else
                <path d="{{ $c['path'] }}"><title>{{ $c['name'] }}</title></path>
            @endif
        @endforeach
    </g>
    <path d="{{ $dep['outline'] }}" fill="none" stroke="var(--blue)" stroke-width="1"/>
    @foreach ($labels as $c)
        <circle class="town" cx="{{ $c['x'] }}" cy="{{ $c['y'] }}" r="1.3" style="pointer-events:none"/>
        <text class="town-label" x="{{ $c['x'] > $vx + $vw * .62 ? $c['x'] - 2.2 : $c['x'] + 2.2 }}" y="{{ $c['y'] + 1.3 }}" text-anchor="{{ $c['x'] > $vx + $vw * .62 ? 'end' : 'start' }}" style="font-size:3.6px;stroke-width:1.2px">{{ $c['name'] }}</text>
    @endforeach
    @if ($base['dep'] === $slug)
        <circle class="base-pulse" cx="{{ $base['x'] }}" cy="{{ $base['y'] }}" r="3.5"/>
        <circle class="base-dot" cx="{{ $base['x'] }}" cy="{{ $base['y'] }}" r="2.3"/>
        <text class="town-label" x="{{ $base['x'] + 3 }}" y="{{ $base['y'] - 3 }}" style="font-size:3.9px;stroke-width:1.2px">{{ $base['name'] }} · le bureau</text>
    @endif
</svg>
