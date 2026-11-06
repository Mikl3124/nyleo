{{-- Index des communes d'un département, par intercommunalité. Attend $slug, $kind. --}}
@php
    $byEpci = \App\Support\Geo::pages($slug)->groupBy(fn ($t) => $t['epci'] ?: 'Autres communes')->sortByDesc(fn ($g) => $g->sum('pop'));
@endphp
<section>
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Communes</span><h2>{{ $kind === 'dp' ? 'La déclaration préalable' : 'Le permis de construire' }} dans votre commune {{ \App\Support\Geo::ofDep($slug) }}</h2></div>
        <div class="town-groups">
        @foreach ($byEpci as $epci => $group)
            <div class="epci-group">
                <h3>{{ $epci }}</h3>
                <ul class="town-index">@foreach ($group->sortBy('name') as $t)<li><a href="{{ \App\Support\Geo::url($t, $kind) }}">{{ $t['name'] }}</a><small>{{ $t['cp'][0] ?? '' }}</small></li>@endforeach</ul>
            </div>
        @endforeach
        </div>
    </div>
</section>
