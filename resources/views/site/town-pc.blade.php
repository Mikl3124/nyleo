@extends('layouts.site')
@php
    use App\Support\Geo;
    use App\Support\TownFacts;
    $base = Geo::base();
    $dep = Geo::department($town['dep']);
    $g = \App\Support\TownGuide::for($town);
    $facts = $g->facts;
    $km = Geo::distance($base, $town);
    $isBase = $town['code'] === $base['code'];
    $dir = $isBase ? '' : Geo::direction($town, $base);
    $cp = implode(', ', $town['cp']);
    $a = Geo::a($town['name']);
    $de = Geo::de($town['name']);
    $c = \App\Models\CompanySetting::current();
    $tel = $c->phone ? preg_replace('/\s+/', '', $c->phone) : null;
    $m = $facts->mairie();
    $note = \App\Support\SiteContent::TOWN_NOTES[$town['code']] ?? null;
    $risks = $facts->risks();
    $heritage = $g->pcHeritage();
    $housing = $facts->housing();
    $faq = $g->pcFaq();
    $summary = $g->summary('pc');
    $metro = str_contains((string) $town['epci'], 'Bordeaux Métropole');
    $tips = collect(\App\Support\SiteContent::DEPARTMENTS[$town['dep']]['tips'] ?? [])
        ->reject(fn ($t) => str_contains($t[0], 'Bordeaux Métropole') && ! $metro)
        ->reject(fn ($t) => $t[0] === 'Des PLU intercommunaux' && ($metro || $facts->docType()))
        ->reject(fn ($t) => in_array($t[0], ['Beaucoup de secteurs protégés', 'Zones à risques', 'Patrimoine', 'Sites protégés', 'Bastides et monuments', 'Patrimoine des bourgs'], true) && $heritage !== null)
        ->reject(fn ($t) => in_array($t[0], ['Sols argileux', 'Assainissement individuel'], true))
        ->values();
@endphp
@section('title')Permis de construire {{ $a }} ({{ $town['cp'][0] ?? $dep['code'] }}) : maison, extension @endsection
@section('description')Permis de construire {{ $a }} ({{ $cp }}){{ $summary ? ' : '.$summary : '' }}. Maison, extension, grange : plans, notice et dépôt en mairie par un dessinateur basé à {{ $base['name'] }}{{ $isBase ? '' : ', à '.$km.' km' }}.@endsection
@include('site.partials.town-ld', ['service' => 'Dossier de permis de construire'])
@section('content')
@include('site.partials.page-hero', [
    'crumbs' => ['Permis de construire' => route('site.pc'), $dep['name'] => Geo::areaUrl($town['dep'], 'pc'), $town['name']],
    'kicker' => 'Permis de construire · '.$town['name'].' · '.$cp,
    'h1' => 'Permis de construire <span class="hl">'.e($a).'</span>',
    'lead' => $g->pcLead(),
    'cta' => ($tel ? '<a class="btn cta" href="tel:'.e($tel).'">Appeler le '.e($c->phone).'</a>' : '').'<a class="btn ghost" href="'.route('site.contact', ['demarche' => 'PC', 'commune' => $town['name'].' ('.($town['cp'][0] ?? '').')']).'">Devis gratuit</a>',
])
<section>
    <div class="wrap town-hero">
        <div>
            <dl class="facts">
                <div><dt>Urbanisme</dt><dd>{{ match ($facts->docType()) { 'PLUi' => 'PLUi', 'PLU', 'PSMV' => 'PLU', 'CC' => 'Carte communale', 'RNU' => 'RNU', default => 'À vérifier' } }}</dd></div>
                <div><dt>Habitants</dt><dd class="num">{{ $town['pop'] ? number_format($town['pop'], 0, ',', ' ') : '—' }}</dd></div>
                <div><dt>Délai maison</dt><dd class="num">{{ $g->pcDelay() }}</dd></div>
                <div><dt>Depuis le bureau</dt><dd class="num">{{ $isBase ? 'Sur place' : $km.' km' }}</dd></div>
            </dl>
            <div style="margin-top:28px">
                <h2>Où et comment construire {{ $a }}</h2>
                @foreach ($facts->urbanism() as $p)<p>{{ $p }}</p>@endforeach
                @if ($town['epci'])<p>{{ $town['name'] }} fait partie {{ TownFacts::epciDe($town['epci']) }}@if ($town['arr']), arrondissement {{ Geo::de($town['arr']) }}@endif. Mon bureau est {{ $isBase ? 'sur la commune même' : 'à '.$km.' km, '.$dir.' '.$de }}.</p>@endif
                @if ($note)<p>{{ $note }}</p>@endif
                @if ($heritage)<p>{{ $heritage }}</p>@endif
            </div>
        </div>
        <figure class="map-card" style="margin:0">
            @include('site.partials.map-commune', ['town' => $town, 'kind' => 'pc'])
            <figcaption class="legend"><span><i style="background:var(--blue)"></i>{{ $town['name'] }}</span><span><i style="background:#c9d6f2"></i>Communes voisines avec leur page</span>@if (! $isBase && $km <= 18)<span><i style="background:var(--ochre);border:1px solid var(--ink)"></i>Le bureau</span>@endif</figcaption>
        </figure>
    </div>
</section>
@if ($risks !== null)
<section class="stone">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Le terrain</span><h2>Ce que le terrain impose {{ $a }}</h2></div>
        @if ($risks)
            <ul class="ticks-list" style="margin-top:12px">
                @foreach ($risks as [$t, $d])<li>@include('site.partials.icon', ['name' => 'check'])<span><b>{{ $t }}</b><span>{{ $d }}</span></span></li>@endforeach
            </ul>
        @else
            <p>Aucun risque naturel majeur ne pèse particulièrement sur les constructions de la commune. Je vérifie quand même la situation de votre parcelle.</p>
        @endif
        @if ($facts->catnat())<p class="muted" style="margin-top:16px">{{ $facts->catnat() }} {{ $facts->catnat() > 1 ? 'arrêtés' : 'arrêté' }} de catastrophe naturelle {{ $facts->catnat() > 1 ? 'ont' : 'a' }} concerné la commune depuis 1982.</p>@endif
    </div>
</section>
@endif
<section>
    <div class="wrap split" style="align-items:start">
        <div>
            <span class="eyebrow">Le dossier</span>
            <h2>Les pièces de votre permis</h2>
            <p>Pour une maison individuelle ou son extension, le formulaire est le CERFA 13406. Je dessine et rédige :</p>
            <ul class="pieces">@foreach (\App\Support\SiteContent::PC_PIECES as [$code, $name, $desc])<li><b>{{ $code }}</b><span>{{ $name }}</span><small>{{ $desc }}</small></li>@endforeach</ul>
        </div>
        <div>
            <span class="eyebrow">À prévoir {{ $a }}</span>
            <h2>Études et attestations</h2>
            <ul class="ticks-list" style="margin-top:12px">@foreach ($g->pcExtras() as [$t, $d])<li>@include('site.partials.icon', ['name' => 'check'])<span><b>{{ $t }}</b><span>{{ $d }}</span></span></li>@endforeach</ul>
            @if ($m && ! empty($m['adresse']))
                <div class="mairie-card">
                    <span class="eyebrow">Où le permis est déposé</span>
                    <p><b>Mairie {{ $de }}</b><br>{{ $m['adresse'] }}<br>{{ $m['cp'] }} {{ $m['ville'] }}@if (! empty($m['telephone']))<br>{{ $m['telephone'] }}@endif</p>
                    <p class="muted">Je dépose le permis en votre nom et je vous transmets le récépissé.</p>
                </div>
            @endif
        </div>
    </div>
</section>
<section class="stone">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Délais</span><h2>Délais d'instruction d'un permis {{ $a }}</h2></div>
        <div class="table-wrap">
            <table class="delays">
                <thead><tr><th scope="col">Projet</th><th scope="col">Délai</th><th scope="col">Avec l'avis de l'ABF</th></tr></thead>
                <tbody>
                    <tr><th scope="row">Maison individuelle ou extension</th><td>2 mois</td><td>3 mois</td></tr>
                    <tr><th scope="row">Autre construction (bâtiment, plusieurs logements)</th><td>3 mois</td><td>4 mois</td></tr>
                </tbody>
            </table>
        </div>
        <p class="muted" style="margin-top:12px">Le délai court à partir du dossier complet. Au-delà de 150 m² de surface, un architecte doit signer le projet : je travaille alors avec un architecte partenaire. Après l'accord, la taxe d'aménagement est calculée par l'administration.@if ($facts->abfLikely()) {{ Str::ucfirst($a) }}, une partie des terrains est concernée par l'avis de l'ABF.@endif</p>
        @if ($tips->isNotEmpty())
            <h3 style="margin-top:28px">Bon à savoir {{ Geo::inDep($town['dep']) }}</h3>
            <ul class="ticks-list" style="margin-top:12px">@foreach ($tips as [$t, $d])<li>@include('site.partials.icon', ['name' => 'check'])<span><b>{{ $t }}</b><span>{{ $d }}</span></span></li>@endforeach</ul>
        @endif
    </div>
</section>
@if (array_sum($housing) > 0)
@php($max = max($housing))
<section class="tight">
    <div class="wrap split" style="align-items:center">
        <div>
            <span class="eyebrow">Construction</span>
            <h2>Combien de logements sont autorisés {{ $a }} ?</h2>
            <p>{{ number_format(array_sum($housing), 0, ',', ' ') }} logements ont été autorisés {{ $a }} de {{ array_key_first($housing) }} à {{ array_key_last($housing) }}, maisons et appartements confondus, soit {{ number_format(array_sum($housing) / count($housing), 0, ',', ' ') }} par an en moyenne.@php($rate = TownFacts::housingRate($housing, (int) $town['pop']))@php($depRate = TownFacts::departmentHousingRate($town['dep']))@if ($rate && $depRate) Rapporté à la population, c'est {{ number_format($rate, 1, ',', ' ') }} logement{{ $rate >= 2 ? 's' : '' }} par an pour 1 000 habitants, {{ $rate > $depRate * 1.15 ? 'plus que' : ($rate < $depRate * 0.85 ? 'moins que' : 'autant que') }} la moyenne {{ Geo::ofDep($town['dep']) }} ({{ number_format($depRate, 1, ',', ' ') }}).@endif</p>
            <p class="muted">Source : base Sitadel du ministère de la Transition écologique.</p>
        </div>
        <ul class="bars" aria-label="Logements autorisés par an {{ $a }}">
            @foreach ($housing as $y => $n)<li><span>{{ $y }}</span><i style="width:{{ $max ? max(2, round($n / $max * 100)) : 0 }}%"></i><b>{{ $n }}</b></li>@endforeach
        </ul>
    </div>
</section>
@endif
<section>
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Déroulé</span><h2>Votre permis, étape par étape</h2></div>
        @include('site.partials.lanes')
        <h3 style="font-size:1.1rem;margin-top:28px">Les guides des grands projets</h3>
        <ul class="near">@foreach (\App\Support\TownGuide::guides('pc') as $guide)<li><a href="{{ route('site.project', $guide['slug']) }}">{{ $guide['name'] }}</a></li>@endforeach</ul>
        <p style="margin-top:22px">Des travaux plus modestes : véranda, abri, piscine, façade ? <a class="link-arrow" href="{{ Geo::url($town, 'dp') }}">La déclaration préalable {{ $a }} @include('site.partials.icon', ['name' => 'arrow'])</a></p>
    </div>
</section>
@include('site.partials.town-bottom', ['kind' => 'pc'])
@endsection
