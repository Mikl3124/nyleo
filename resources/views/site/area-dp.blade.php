@extends('layouts.site')
@php
    use App\Support\Geo;
    $base = Geo::base();
    $all = Geo::communes($slug);
    $stats = \App\Support\TownFacts::department($slug);
    $in = Geo::inDep($slug);
    $of = Geo::ofDep($slug);
    $withPlu = $stats ? ($stats['types']['PLUi'] ?? 0) + ($stats['types']['PLU'] ?? 0) : null;
    $withoutPlu = $stats ? ($stats['types']['CC'] ?? 0) + ($stats['types']['RNU'] ?? 0) : null;
    $depFaq = array_values(array_filter([
        ["Quels travaux demandent une déclaration préalable {$in} ?", "Une extension ou une annexe de 5 à 20 m² (jusqu'à 40 m² en zone urbaine d'un PLU), une piscine de 10 à 100 m², une modification de façade, de fenêtres ou de toiture, la pose de panneaux solaires, et, selon la commune, une clôture ou un ravalement. Le changement de destination sans toucher à la structure se déclare aussi."],
        $stats ? ["Dans quelles communes une extension de 40 m² se déclare-t-elle {$in} ?", "Dans les zones urbaines des {$withPlu} communes couvertes par un PLU communal ou intercommunal. Dans les {$withoutPlu} communes avec une carte communale ou le règlement national, le seuil reste à 20 m²."] : null,
        ["Combien de temps dure une déclaration préalable {$in} ?", "Un mois après le dépôt d'un dossier complet. Deux mois si le terrain est dans les abords d'un monument historique, dans un site patrimonial remarquable ou dans un site classé, parce que l'Architecte des Bâtiments de France donne son avis."],
        ["Faut-il se déplacer pour une déclaration préalable {$in} ?", "Non. Mon bureau est à {$base['name']} (24) : vos photos, quelques mesures et le cadastre suffisent dans la plupart des cas. Je dépose le dossier en ligne ou en mairie, en votre nom."],
    ]));
@endphp
@push('head')
<script type="application/ld+json">{!! json_encode(['@@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => collect($depFaq)->map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]])->all()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('title', 'Déclaration préalable de travaux '.$in.' ('.$dep['code'].')')
@section('description', "Déclaration préalable {$in} : véranda, abri, piscine, clôture, façade. Seuils, délais et avis de l'ABF dans les {$all->count()} communes {$of}, dossier déposé pour vous.")
@section('content')
@include('site.partials.page-hero', [
    'crumbs' => ['Déclaration préalable' => route('site.dp'), $dep['name']], 'kicker' => 'Déclaration préalable · '.$dep['name'].' ('.$dep['code'].')',
    'h1' => 'Déclaration préalable de travaux <span class="hl">'.e($in).'</span>',
    'lead' => $content['dp_intro'],
    'cta' => '<a class="btn cta" href="'.route('site.contact', ['demarche' => 'DP']).'">Demander un devis</a>',
])
<section>
    <div class="wrap split" style="align-items:start">
        <div>
            <dl class="facts" style="grid-template-columns:1fr 1fr">
                <div><dt>Communes</dt><dd class="num">{{ $all->count() }}</dd></div>
                <div><dt>Délai</dt><dd class="num">1 mois</dd></div>
                @if ($stats)
                    <div><dt>Monuments protégés</dt><dd class="num">{{ number_format($stats['monuments'], 0, ',', ' ') }}</dd></div>
                    <div><dt>Communes avec PLU</dt><dd class="num">{{ $withPlu }}</dd></div>
                @else
                    <div><dt>Près d'un monument</dt><dd class="num">2 mois</dd></div>
                    <div><dt>Bureau</dt><dd>{{ $base['name'] }} (24)</dd></div>
                @endif
            </dl>
            <h2 style="margin-top:36px">Votre commune</h2>
            <p class="muted">Choisissez votre commune : sa page dit quelle démarche s'applique à vos travaux.</p>
            @include('site.partials.town-finder', ['slug' => $slug, 'kind' => 'dp'])
            <h3 style="margin-top:32px">Les petits travaux {{ $in }}</h3>
            <ul class="ticks-list">@foreach ($content['dp_tips'] as [$t, $d])<li>@include('site.partials.icon', ['name' => 'check'])<span><b>{{ $t }}</b><span>{{ $d }}</span></span></li>@endforeach</ul>
        </div>
        <div class="map-card">
            @include('site.partials.map-department', ['slug' => $slug, 'kind' => 'dp'])
            <div class="legend"><span><i style="background:#c9d6f2"></i>Commune avec sa page</span><span><i style="background:var(--blue-l)"></i>Autre commune</span>@if ($base['dep'] === $slug)<span><i style="background:var(--ochre);border:1px solid var(--ink)"></i>Le bureau</span>@endif</div>
        </div>
    </div>
</section>
@if ($stats && ($stats['spr']->isNotEmpty() || $stats['mh_top']->isNotEmpty()))
<section class="stone">
    <div class="wrap split" style="align-items:start">
        <div>
            <span class="eyebrow">Patrimoine</span>
            <h2>Où l'Architecte des Bâtiments de France donne son avis</h2>
            <p>{{ number_format($stats['monuments'], 0, ',', ' ') }} monuments historiques sont protégés {{ $in }}. Dans leurs abords, un changement de fenêtres, un ravalement, une clôture ou un abri de jardin passe par l'ABF : le délai de la déclaration passe à deux mois.</p>
            @if ($stats['spr']->isNotEmpty())
                <h3 style="margin-top:20px">Sites patrimoniaux remarquables</h3>
                <p class="muted">Tous les travaux extérieurs s'y déclarent, même les plus petits.</p>
                <ul class="near">@foreach ($stats['spr'] as $f)<li>@if ($f->town['page'])<a href="{{ Geo::url($f->town, 'dp') }}">{{ $f->town['name'] }}</a>@else<span>{{ $f->town['name'] }}</span>@endif</li>@endforeach</ul>
            @endif
        </div>
        @if ($stats['mh_top']->isNotEmpty())
        <div>
            <h3>Les communes les plus riches en monuments</h3>
            <ul class="mh-list">@foreach ($stats['mh_top'] as $f)<li>@if ($f->town['page'])<a href="{{ Geo::url($f->town, 'dp') }}">{{ $f->town['name'] }}</a>@else<span>{{ $f->town['name'] }}</span>@endif<small>{{ $f->monuments()->count() }} monuments</small></li>@endforeach</ul>
        </div>
        @endif
    </div>
</section>
@endif
@include('site.partials.area-towns', ['kind' => 'dp'])
<section class="stone">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Questions</span><h2>Déclaration préalable {{ $in }} : vos questions</h2></div>
        <div class="faq">@foreach ($depFaq as [$q, $r])<details><summary>{{ $q }}</summary><p>{{ $r }}</p></details>@endforeach</div>
        <p style="margin-top:22px">Une maison neuve ou une grande extension ? <a class="link-arrow" href="{{ Geo::areaUrl($slug, 'pc') }}">Le permis de construire {{ $in }} @include('site.partials.icon', ['name' => 'arrow'])</a></p>
    </div>
</section>
@include('site.partials.cta')
@endsection
