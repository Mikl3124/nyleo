{{-- Pages par département et grandes villes pour une démarche. Attend $kind. --}}
<section class="stone">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Près de chez vous</span><h2>{{ $kind === 'dp' ? 'La déclaration préalable' : 'Le permis de construire' }} dans votre département</h2></div>
        <ul class="near">@foreach (\App\Support\Geo::departmentSlugs() as $d)<li><a href="{{ \App\Support\Geo::areaUrl($d, $kind) }}">{{ \App\Support\SiteContent::DEPARTMENTS[$d]['name'] }} <small>{{ \App\Support\SiteContent::DEPARTMENTS[$d]['code'] }}</small></a></li>@endforeach</ul>
        <h3 style="font-size:1.1rem;margin-top:24px">Grandes villes</h3>
        <ul class="near">@foreach (\App\Support\Geo::pages()->take(16)->sortBy('name') as $t)<li><a href="{{ \App\Support\Geo::url($t, $kind) }}">{{ $t['name'] }}</a></li>@endforeach</ul>
    </div>
</section>
