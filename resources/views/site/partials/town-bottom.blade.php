{{-- Bas commun des pages d'une commune : questions, communes proches, sources. Attend $town, $kind, $faq. --}}
@php
    use App\Support\Geo;
    use App\Support\TownFacts;
    $dep = Geo::department($town['dep']);
    $near = Geo::nearby($town, 8);
    $siblings = Geo::sameEpci($town)->reject(fn ($t) => $near->contains('code', $t['code']));
    $updated = TownFacts::updated();
    $label = $kind === 'dp' ? 'Déclaration préalable' : 'Permis de construire';
@endphp
@if ($faq)
<section class="stone">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Questions</span><h2>{{ $label }} {{ Geo::a($town['name']) }} : vos questions</h2></div>
        <div class="faq">@foreach ($faq as [$q, $r])<details><summary>{{ $q }}</summary><p>{{ $r }}</p></details>@endforeach</div>
    </div>
</section>
@endif
<section class="tight">
    <div class="wrap">
        <h2 style="font-size:1.4rem">{{ $label }} près {{ Geo::de($town['name']) }}</h2>
        <ul class="near">@foreach ($near as $n)<li><a href="{{ Geo::url($n, $kind) }}">{{ $n['name'] }} <small>{{ Geo::distance($town, $n) }} km</small></a></li>@endforeach<li><a href="{{ Geo::areaUrl($town['dep'], $kind) }}">{{ Str::ucfirst($dep['all']) }}</a></li></ul>
        @if ($siblings->isNotEmpty())
            <h3 style="font-size:1.1rem;margin-top:28px">Autres communes {{ TownFacts::epciDe($town['epci']) }}</h3>
            <ul class="near">@foreach ($siblings as $n)<li><a href="{{ Geo::url($n, $kind) }}">{{ $n['name'] }}</a></li>@endforeach</ul>
        @endif
        @if ($updated)<p class="muted sources">Sources : Géoportail de l'urbanisme, base Mérimée du ministère de la Culture, Géorisques, Annuaire de l'administration, Sitadel (données publiques, mises à jour le {{ \Illuminate\Support\Carbon::parse($updated)->translatedFormat('j F Y') }}). Elles ne remplacent pas le règlement de votre zone : je vérifie votre parcelle avant le devis.</p>@endif
    </div>
</section>
@include('site.partials.cta')
