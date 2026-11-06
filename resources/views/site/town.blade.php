@extends('layouts.site')
@php
    use App\Support\Geo;
    use App\Support\TownFacts;
    $base = Geo::base();
    $dep = Geo::department($town['dep']);
    $km = Geo::distance($base, $town);
    $isBase = $town['code'] === $base['code'];
    $dir = $isBase ? '' : Geo::direction($town, $base);
    $near = Geo::nearby($town, 8);
    $cp = implode(', ', $town['cp']);
    $c = \App\Models\CompanySetting::current();
    $tel = $c->phone ? preg_replace('/\s+/', '', $c->phone) : null;
    $a = Geo::a($town['name']);
    $de = Geo::de($town['name']);
    $where = $isBase ? 'ici même, '.$a : 'à '.$km.' km, '.$dir.' '.$de;
    $mairie = 'la mairie '.$de;
    $note = \App\Support\SiteContent::TOWN_NOTES[$town['code']] ?? null;
    $metro = str_contains((string) $town['epci'], 'Bordeaux Métropole');
    $facts = TownFacts::for($town);
    $heritage = $facts->heritage();
    $monuments = $facts->monuments();
    $risks = $facts->risks();
    $faq = $facts->faq();
    $m = $facts->mairie();
    $siblings = Geo::sameEpci($town)->reject(fn ($t) => $near->contains('code', $t['code']));
    // Conseils du département qui ne sont pas déjà couverts par les données de la commune.
    $tips = collect(\App\Support\SiteContent::DEPARTMENTS[$town['dep']]['tips'] ?? [])
        ->reject(fn ($t) => str_contains($t[0], 'Bordeaux Métropole') && ! $metro)
        ->reject(fn ($t) => $t[0] === 'Des PLU intercommunaux' && ($metro || $facts->docType()))
        ->reject(fn ($t) => in_array($t[0], ['Beaucoup de secteurs protégés', 'Zones à risques'], true) && $heritage !== null)
        ->values();
    $summary = $facts->summary();
    $updated = TownFacts::updated();
@endphp
@section('title')Permis de construire et déclaration préalable {{ $a }} ({{ $town['cp'][0] ?? $dep['code'] }})@endsection
@section('description')Déclaration préalable et permis de construire {{ $a }} ({{ $cp }}){{ $summary ? ' : '.$summary : '' }}. Plans, CERFA et dépôt en mairie par un dessinateur basé à {{ $base['name'] }}{{ $isBase ? '' : ', à '.$km.' km' }}.@endsection
@push('head')
<script type="application/ld+json">{!! json_encode([
    '@@context' => 'https://schema.org',
    '@graph' => array_values(array_filter([
        [
            '@type' => 'Service',
            'name' => 'Plans de déclaration préalable et de permis de construire '.$a,
            'serviceType' => 'Dossier de permis de construire et de déclaration préalable',
            'provider' => ['@id' => url('/').'#entreprise'],
            'areaServed' => ['@type' => 'City', 'name' => $town['name'], 'address' => ['@type' => 'PostalAddress', 'postalCode' => $town['cp'][0] ?? null, 'addressLocality' => $town['name'], 'addressRegion' => $dep['name'], 'addressCountry' => 'FR']],
            'url' => url()->current(),
        ],
        $faq ? ['@type' => 'FAQPage', 'mainEntity' => collect($faq)->map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]])->all()] : null,
    ])),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('content')
@include('site.partials.page-hero', [
    'crumbs' => ['Secteurs' => route('site.areas'), $dep['name'] => route('site.area', $town['dep']), $town['name']],
    'kicker' => $town['name'].' · '.$cp,
    'h1' => 'Permis de construire et déclaration préalable <span class="hl">'.e($a).'</span>',
    'lead' => "Véranda, extension, piscine, pergola ou maison : je dessine vos plans selon les règles d'urbanisme {$a} et je dépose votre dossier à {$mairie}. Mon bureau est {$where}.",
    'cta' => ($tel ? '<a class="btn cta" href="tel:'.e($tel).'">Appeler le '.e($c->phone).'</a>' : '').'<a class="btn ghost" href="'.route('site.contact', ['commune' => $town['name'].' ('.($town['cp'][0] ?? '').')']).'">Devis gratuit</a>',
])
<section>
    <div class="wrap town-hero">
        <div>
            <dl class="facts">
                <div><dt>Code postal</dt><dd class="num">{{ $cp }}</dd></div>
                <div><dt>Habitants</dt><dd class="num">{{ $town['pop'] ? number_format($town['pop'], 0, ',', ' ') : '—' }}</dd></div>
                @if ($facts->docLabel())<div><dt>Urbanisme</dt><dd>{{ $facts->docLabel() }}</dd></div>@else<div><dt>Département</dt><dd>{{ $dep['name'] }} ({{ $dep['code'] }})</dd></div>@endif
                <div><dt>Depuis le bureau</dt><dd class="num">{{ $isBase ? 'Sur place' : $km.' km' }}</dd></div>
            </dl>
            <div style="margin-top:28px">
                <h2>Les règles d'urbanisme {{ $a }}</h2>
                @foreach ($facts->urbanism() as $p)<p>{{ $p }}</p>@endforeach
                @if ($town['epci'])<p>{{ $town['name'] }} fait partie {{ TownFacts::epciDe($town['epci']) }}@if ($town['arr']), arrondissement {{ Geo::de($town['arr']) }}@endif.</p>@endif
                @if ($note)<p>{{ $note }}</p>@endif
                @if ($m && ! empty($m['adresse']))
                    <div class="mairie-card">
                        <span class="eyebrow">Où le dossier est déposé</span>
                        <p><b>Mairie {{ $de }}</b><br>{{ $m['adresse'] }}<br>{{ $m['cp'] }} {{ $m['ville'] }}@if (! empty($m['telephone']))<br>{{ $m['telephone'] }}@endif</p>
                        <p class="muted">Je dépose le dossier en votre nom, en ligne ou au guichet, et je vous transmets le récépissé.</p>
                    </div>
                @endif
            </div>
        </div>
        <figure class="map-card" style="margin:0">
            @include('site.partials.map-commune', ['town' => $town])
            <figcaption class="legend"><span><i style="background:var(--blue)"></i>{{ $town['name'] }}</span><span><i style="background:#c9d6f2"></i>Communes voisines avec leur page</span>@if (! $isBase && $km <= 18)<span><i style="background:var(--ochre);border:1px solid var(--ink)"></i>Le bureau</span>@endif</figcaption>
        </figure>
    </div>
</section>
@if ($heritage !== null || $risks)
<section class="stone">
    <div class="wrap split" style="align-items:start">
        @if ($heritage !== null)
        <div>
            <span class="eyebrow">Patrimoine</span>
            <h2>Monuments historiques et Architecte des Bâtiments de France</h2>
            @foreach ($heritage as $p)<p>{{ $p }}</p>@endforeach
            @if ($monuments->isNotEmpty())
                <ul class="mh-list">
                    @foreach ($monuments->take(10) as $mh)<li><span>{{ $mh['n'] }}</span><small>{{ $mh['p'] }}</small></li>@endforeach
                    @if ($monuments->count() > 10)<li class="muted">et {{ $monuments->count() - 10 }} autres</li>@endif
                </ul>
            @endif
        </div>
        @endif
        @if ($risks !== null)
        <div>
            <span class="eyebrow">Risques</span>
            <h2>Les risques à prendre en compte {{ $a }}</h2>
            @if ($risks)
                <ul class="ticks-list" style="margin-top:12px">
                    @foreach ($risks as [$t, $d])<li>@include('site.partials.icon', ['name' => 'check'])<span><b>{{ $t }}</b><span>{{ $d }}</span></span></li>@endforeach
                </ul>
            @else
                <p>Aucun risque naturel majeur ne pèse particulièrement sur les projets de construction de la commune. Je vérifie quand même la situation de votre parcelle.</p>
            @endif
            @if ($facts->catnat())<p class="muted">{{ $facts->catnat() }} {{ $facts->catnat() > 1 ? 'arrêtés' : 'arrêté' }} de catastrophe naturelle {{ $facts->catnat() > 1 ? 'ont' : 'a' }} concerné la commune depuis 1982.</p>@endif
        </div>
        @endif
    </div>
</section>
@endif
<section>
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Délais</span><h2>Délais d'instruction {{ $a }}</h2></div>
        <div class="table-wrap">
            <table class="delays">
                <thead><tr><th scope="col">Projet</th><th scope="col">Délai</th><th scope="col">Avec l'avis de l'ABF</th></tr></thead>
                <tbody>@foreach ($facts->delays() as [$what, $d, $abf])<tr><th scope="row">{{ $what }}</th><td>{{ $d }}</td><td>{{ $abf }}</td></tr>@endforeach</tbody>
            </table>
        </div>
        <p class="muted" style="margin-top:12px">Le délai court à partir du dépôt d'un dossier complet. La mairie a un mois pour réclamer une pièce manquante : je m'en occupe.@if ($facts->abfLikely()) {{ Str::ucfirst($a) }}, une partie des terrains est concernée par l'avis de l'ABF.@endif</p>
        @if ($tips->isNotEmpty())
            <h3 style="margin-top:28px">Bon à savoir {{ $a }}</h3>
            <ul class="ticks-list" style="margin-top:12px">
                @foreach ($tips as [$t, $d])<li>@include('site.partials.icon', ['name' => 'check'])<span><b>{{ $t }}</b><span>{{ $d }}</span></span></li>@endforeach
            </ul>
        @endif
    </div>
</section>
@php($housing = $facts->housing())
@if (array_sum($housing) > 0)
@php($max = max($housing))
<section class="tight">
    <div class="wrap split" style="align-items:center">
        <div>
            <span class="eyebrow">Construction</span>
            <h2>Combien de logements sont autorisés {{ $a }} ?</h2>
            <p>{{ number_format(array_sum($housing), 0, ',', ' ') }} logements ont été autorisés {{ $a }} de {{ array_key_first($housing) }} à {{ array_key_last($housing) }}, maisons et appartements confondus, soit {{ number_format(array_sum($housing) / count($housing), 0, ',', ' ') }} par an en moyenne.@php($rate = TownFacts::housingRate($housing, (int) $town['pop']))@php($depRate = TownFacts::departmentHousingRate($town['dep']))@if ($rate && $depRate) Rapporté à la population, c'est {{ number_format($rate, 1, ',', ' ') }} logement{{ $rate >= 2 ? 's' : '' }} par an pour 1 000 habitants, {{ $rate > $depRate * 1.15 ? 'plus que' : ($rate < $depRate * 0.85 ? 'moins que' : 'autant que') }} la moyenne {{ $town['dep'] === 'gironde' ? 'de la Gironde' : 'de la Dordogne' }} ({{ number_format($depRate, 1, ',', ' ') }}).@endif Ces chiffres ne comptent pas les extensions, piscines ou vérandas, qui représentent l'essentiel des déclarations préalables.</p>
            <p class="muted">Source : base Sitadel du ministère de la Transition écologique.</p>
        </div>
        <ul class="bars" aria-label="Logements autorisés par an {{ $a }}">
            @foreach ($housing as $y => $n)<li><span>{{ $y }}</span><i style="width:{{ $max ? max(2, round($n / $max * 100)) : 0 }}%"></i><b>{{ $n }}</b></li>@endforeach
        </ul>
    </div>
</section>
@endif
<section class="stone">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Simulateur</span><h2>Quelle démarche pour votre projet {{ $a }} ?</h2></div>
        @include('site.partials.simulator', ['prices' => $prices])
        <p style="margin-top:18px">En savoir plus : <a href="{{ route('site.dp') }}">la déclaration préalable</a>, <a href="{{ route('site.pc') }}">le permis de construire</a>, <a href="{{ route('site.pricing') }}">mes tarifs</a>.</p>
    </div>
</section>
<section>
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Déroulé</span><h2>Comment ça se passe</h2></div>
        @include('site.partials.lanes')
    </div>
</section>
@if ($faq)
<section class="stone">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Questions</span><h2>Vos questions sur l'urbanisme {{ $a }}</h2></div>
        <div class="faq">@foreach ($faq as [$q, $r])<details><summary>{{ $q }}</summary><p>{{ $r }}</p></details>@endforeach</div>
    </div>
</section>
@endif
<section class="tight">
    <div class="wrap">
        <h2 style="font-size:1.4rem">J'interviens aussi près {{ $de }}</h2>
        <ul class="near">@foreach ($near as $n)<li><a href="{{ Geo::url($n) }}">{{ $n['name'] }} <small>{{ Geo::distance($town, $n) }} km</small></a></li>@endforeach<li><a href="{{ route('site.area', $town['dep']) }}">Toute la {{ $dep['name'] }}</a></li></ul>
        @if ($siblings->isNotEmpty())
            <h3 style="font-size:1.1rem;margin-top:28px">Autres communes {{ TownFacts::epciDe($town['epci']) }}</h3>
            <ul class="near">@foreach ($siblings as $n)<li><a href="{{ Geo::url($n) }}">{{ $n['name'] }}</a></li>@endforeach</ul>
        @endif
        @if ($updated)<p class="muted sources">Sources : Géoportail de l'urbanisme, base Mérimée du ministère de la Culture, Géorisques, Annuaire de l'administration (données publiques, mises à jour le {{ \Illuminate\Support\Carbon::parse($updated)->translatedFormat('j F Y') }}). Elles ne remplacent pas le règlement de votre zone : je vérifie votre parcelle avant le devis.</p>@endif
    </div>
</section>
@include('site.partials.cta')
@endsection
