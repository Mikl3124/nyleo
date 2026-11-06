{{-- Zoom sur une commune et ses voisines --}}
@php
    $g = \App\Support\Geo::class;
    $half = 20;
    $base = $g::base();
    [$x0, $x1, $y0, $y1] = [$town['x'] - $half, $town['x'] + $half, $town['y'] - $half, $town['y'] + $half];
    // Seules les communes dont le tracé entre dans le cadre sont dessinées (et donc cliquables).
    $around = $g::communes()
        ->filter(fn ($c) => abs($c['x'] - $town['x']) < $half + 12 && abs($c['y'] - $town['y']) < $half + 12)
        ->filter(function ($c) use ($x0, $x1, $y0, $y1) {
            preg_match_all('/(-?\d+(?:\.\d+)?)[ ,](-?\d+(?:\.\d+)?)/', $c['path'], $m);

            return $m[1] && max($m[1]) > $x0 && min($m[1]) < $x1 && max($m[2]) > $y0 && min($m[2]) < $y1;
        });
    $near = $g::spread($around->reject(fn ($c) => $c['code'] === $town['code'])->sortByDesc('pop')->filter(fn ($c) => abs($c['x'] - $town['x']) + mb_strlen($c['name']) * .42 < $half - 1 && abs($c['y'] - $town['y']) < $half - 2), 8, ['x' => $town['x'], 'y' => $town['y'] - 1.6])->take(6);
    $sx = $town['x'] - $half + 2;
    $sy = $town['y'] + $half - 2.5;
    $baseIn = abs($base['x'] - $town['x']) < $half && abs($base['y'] - $town['y']) < $half && $base['code'] !== $town['code'];
@endphp
<svg class="map" viewBox="{{ $g::zoomBox($town, $half) }}" role="img" aria-labelledby="map-c-t" style="background:var(--stone);border-radius:10px">
    <title id="map-c-t">{{ $town['name'] }} et les communes voisines</title>
    <g class="mesh" style="--sw:.15">
        @foreach ($around as $c)
            @if ($c['code'] === $town['code'])
                <path class="on" d="{{ $c['path'] }}"/>
            @elseif ($c['page'])
                <a href="{{ $g::url($c) }}"><title>{{ $c['name'] }}</title><path d="{{ $c['path'] }}" style="stroke-width:.18"/></a>
            @else
                <path d="{{ $c['path'] }}" style="stroke-width:.18"><title>{{ $c['name'] }}</title></path>
            @endif
        @endforeach
    </g>
    @foreach ($near as $c)
        <text class="town-label" x="{{ $c['x'] }}" y="{{ $c['y'] }}" text-anchor="middle" style="font-size:1.45px;stroke-width:.45px">{{ $c['name'] }}</text>
    @endforeach
    <circle cx="{{ $town['x'] }}" cy="{{ $town['y'] }}" r=".7" fill="#fff" stroke="var(--ink)" stroke-width=".3"/>
    <text class="town-label" x="{{ $town['x'] }}" y="{{ $town['y'] - 1.6 }}" text-anchor="middle" style="font-size:2.3px;fill:#fff;stroke:var(--blue);stroke-width:.6px">{{ $town['name'] }}</text>
    @if ($baseIn)
        <circle class="base-dot" cx="{{ $base['x'] }}" cy="{{ $base['y'] }}" r=".9" style="stroke-width:.35"/>
        <text class="town-label" x="{{ $base['x'] + 1.4 }}" y="{{ $base['y'] + .5 }}" style="font-size:1.5px;stroke-width:.45px">Le bureau</text>
    @endif
    <g stroke="var(--ink)" stroke-width=".25"><path d="M{{ $sx }} {{ $sy }}h5M{{ $sx }} {{ $sy - .6 }}v1.2M{{ $sx + 5 }} {{ $sy - .6 }}v1.2"/></g>
    <text x="{{ $sx + 2.5 }}" y="{{ $sy - 1 }}" text-anchor="middle" style="font-family:var(--mono);font-size:1.3px;fill:var(--ink)">5 km</text>
</svg>
