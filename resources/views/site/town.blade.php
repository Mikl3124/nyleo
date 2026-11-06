@extends('layouts.site')
@php
    use App\Support\Geo;
    $base = Geo::base();
    $dep = Geo::department($town['dep']);
    $km = Geo::distance($base, $town);
    $isBase = $town['code'] === $base['code'];
    $dir = $isBase ? '' : Geo::direction($town, $base);
    $near = Geo::nearby($town, 8);
    $cp = implode(', ', $town['cp']);
    $c = \App\Models\CompanySetting::current();
    $tel = $c->phone ? preg_replace('/\s+/', '', $c->phone) : null;
    $where = $isBase ? 'ici même, à '.$town['name'] : 'à '.$km.' km, '.$dir.' de '.$town['name'];
@endphp
@section('title', 'Permis de construire et déclaration préalable à '.$town['name'].' ('.($town['cp'][0] ?? $dep['code']).')')
@section('description', "Plans de déclaration préalable et de permis de construire à {$town['name']} ({$cp}) : dessin, CERFA et dépôt en mairie en votre nom. Dessinateur basé à {$base['name']}, à {$km} km.")
@section('content')
@include('site.partials.page-hero', [
    'crumbs' => ['Secteurs' => route('site.areas'), $dep['name'] => route('site.area', $town['dep']), $town['name']],
    'kicker' => $town['name'].' · '.$cp,
    'h1' => 'Permis de construire et déclaration préalable <span class="hl">à '.e($town['name']).'</span>',
    'lead' => "Véranda, extension, piscine, pergola ou maison : je dessine vos plans et je dépose votre dossier à la mairie de {$town['name']}. Mon bureau est {$where}.",
    'cta' => ($tel ? '<a class="btn cta" href="tel:'.$tel.'">Appeler le '.e($c->phone).'</a>' : '').'<a class="btn ghost" href="'.route('site.contact', ['commune' => $town['name'].' ('.($town['cp'][0] ?? '').')']).'">Devis pour '.e($town['name']).'</a>',
])
<section>
    <div class="wrap town-hero">
        <div>
            <dl class="facts">
                <div><dt>Code postal</dt><dd class="num">{{ $cp }}</dd></div>
                <div><dt>Habitants</dt><dd class="num">{{ $town['pop'] ? number_format($town['pop'], 0, ',', ' ') : '—' }}</dd></div>
                <div><dt>Depuis le bureau</dt><dd class="num">{{ $isBase ? 'Sur place' : $km.' km' }}</dd></div>
                <div><dt>Département</dt><dd>{{ $dep['name'] }} ({{ $dep['code'] }})</dd></div>
            </dl>
            <div style="margin-top:28px">
                <h2>Votre projet à {{ $town['name'] }}</h2>
                <p>À {{ $town['name'] }} comme ailleurs, la démarche dépend de la surface créée et du règlement d'urbanisme de la commune (PLU, carte communale ou règlement national). Avant le devis, je consulte le document en vigueur pour votre parcelle et je vous dis quelle démarche s'applique.</p>
                @if ($town['epci'])<p>{{ $town['name'] }} fait partie de <b>{{ $town['epci'] }}</b>@if ($town['arr']), arrondissement de {{ $town['arr'] }}@endif. Si un PLU intercommunal s'applique, je l'intègre à votre dossier.</p>@endif
                <ul class="ticks-list" style="margin-top:20px">
                    <li>@include('site.partials.icon', ['name' => 'check'])<span><b>Déclaration préalable</b><span>Véranda, abri, piscine, façade, clôture. Instruction : 1 mois.</span></span></li>
                    <li>@include('site.partials.icon', ['name' => 'check'])<span><b>Permis de construire</b><span>Maison, extension de plus de 20 m² (40 m² en zone U). Instruction : 2 mois.</span></span></li>
                    <li>@include('site.partials.icon', ['name' => 'check'])<span><b>Dépôt en votre nom</b><span>Sur le guichet en ligne ou à la mairie de {{ $town['name'] }}, avec le récépissé dans votre espace.</span></span></li>
                </ul>
            </div>
        </div>
        <figure class="map-card" style="margin:0">
            @include('site.partials.map-commune', ['town' => $town])
            <figcaption class="legend"><span><i style="background:var(--blue)"></i>{{ $town['name'] }}</span><span><i style="background:#c9d6f2"></i>Communes voisines avec leur page</span>@if (! $isBase && $km <= 18)<span><i style="background:var(--ochre);border:1px solid var(--ink)"></i>Le bureau</span>@endif</figcaption>
        </figure>
    </div>
</section>
<section class="stone">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Simulateur</span><h2>Quelle démarche pour votre projet à {{ $town['name'] }} ?</h2></div>
        @include('site.partials.simulator', ['prices' => $prices])
    </div>
</section>
<section>
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Déroulé</span><h2>Comment ça se passe</h2></div>
        @include('site.partials.lanes')
    </div>
</section>
<section class="stone tight">
    <div class="wrap">
        <h2 style="font-size:1.4rem">J'interviens aussi près de {{ $town['name'] }}</h2>
        <ul class="near">@foreach ($near as $n)<li><a href="{{ Geo::url($n) }}">{{ $n['name'] }} <small>{{ Geo::distance($town, $n) }} km</small></a></li>@endforeach<li><a href="{{ route('site.area', $town['dep']) }}">Toute la {{ $dep['name'] }}</a></li></ul>
    </div>
</section>
@include('site.partials.cta')
@endsection
