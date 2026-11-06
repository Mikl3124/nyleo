@extends('layouts.site')
@php
    use App\Support\Geo;
    $base = Geo::base();
    $pages = Geo::pages($slug);
    $all = Geo::communes($slug);
    $pop = $all->sum('pop');
    $stats = \App\Support\TownFacts::department($slug);
    $byEpci = $pages->groupBy(fn ($t) => $t['epci'] ?: 'Autres communes')->sortByDesc(fn ($g) => $g->sum('pop'));
    $label = ['PLUi' => 'PLU intercommunal', 'PLU' => 'PLU communal', 'CC' => 'carte communale', 'RNU' => 'règlement national (RNU)'];
    $in = 'en '.$dep['name'];
    $depFaq = array_values(array_filter([
        $stats ? ["Quelles communes ont un PLU {$in} ?", collect(['PLUi', 'PLU', 'CC', 'RNU'])->filter(fn ($k) => ! empty($stats['types'][$k]))->map(fn ($k) => $stats['types'][$k].' '.($stats['types'][$k] > 1 ? 'communes' : 'commune').' '.($k === 'RNU' ? 'relèvent du' : 'ont un').' '.$label[$k])->implode(', ').". Le type de document change les seuils : sans PLU, une extension de plus de 20 m² demande un permis de construire." ] : null,
        ["Combien de temps pour obtenir une déclaration préalable {$in} ?", "Un mois après le dépôt d'un dossier complet, deux mois si le terrain est dans les abords d'un monument historique ou dans un site patrimonial remarquable. Le permis de construire d'une maison demande deux mois, trois avec l'avis de l'Architecte des Bâtiments de France."],
        ["Intervenez-vous dans toute la {$dep['name']} ?", "Oui. Mon bureau est à {$base['name']} (24) et je travaille à distance pour les {$all->count()} communes du département : un appel, vos photos et le cadastre suffisent dans la plupart des cas."],
    ]));
@endphp
@push('head')
<script type="application/ld+json">{!! json_encode(['@@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => collect($depFaq)->map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]])->all()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('title', 'Permis de construire et déclaration préalable en '.$dep['name'].' ('.$dep['code'].')')
@section('description', "Plans et dépôt de vos dossiers de déclaration préalable et de permis de construire dans les {$all->count()} communes de {$dep['name']}, par un dessinateur basé à {$base['name']}.")
@section('content')
@include('site.partials.page-hero', [
    'crumbs' => ['Secteurs' => route('site.areas'), $dep['name']], 'kicker' => 'Département '.$dep['code'],
    'h1' => 'Plans et permis <span class="hl">en '.e($dep['name']).'</span>',
    'lead' => $content['intro'],
    'cta' => '<a class="btn cta" href="'.route('site.contact').'">Demander un devis</a>',
])
<section>
    <div class="wrap split" style="align-items:start">
        <div>
            <dl class="facts" style="grid-template-columns:1fr 1fr">
                <div><dt>Communes</dt><dd class="num">{{ $all->count() }}</dd></div>
                <div><dt>Habitants</dt><dd class="num">{{ number_format($pop, 0, ',', ' ') }}</dd></div>
                <div><dt>Distance max.</dt><dd class="num">{{ $all->max('km') }} km</dd></div>
                <div><dt>Bureau</dt><dd>{{ $base['name'] }} (24)</dd></div>
            </dl>
            <h2 style="margin-top:36px">Trouvez votre commune</h2>
            <p class="muted">Les communes en bleu foncé ont leur page. Pour toutes les autres, j'interviens aussi.</p>
            @include('site.partials.town-finder', ['slug' => $slug])
            <h3 style="margin-top:32px">Bon à savoir en {{ $dep['name'] }}</h3>
            <ul class="ticks-list">@foreach ($content['tips'] as [$t, $d])<li>@include('site.partials.icon', ['name' => 'check'])<span><b>{{ $t }}</b><span>{{ $d }}</span></span></li>@endforeach</ul>
        </div>
        <div class="map-card">
            @include('site.partials.map-department', ['slug' => $slug])
            <div class="legend"><span><i style="background:#c9d6f2"></i>Commune avec sa page</span><span><i style="background:var(--blue-l)"></i>Autre commune</span>@if ($base['dep'] === $slug)<span><i style="background:var(--ochre);border:1px solid var(--ink)"></i>Le bureau</span>@endif</div>
        </div>
    </div>
</section>
@if ($stats)
<section class="stone">
    <div class="wrap split" style="align-items:start">
        <div>
            <span class="eyebrow">Urbanisme</span>
            <h2>Les documents d'urbanisme {{ $in }}</h2>
            <dl class="facts" style="grid-template-columns:1fr 1fr;margin-top:18px">
                @foreach (['PLUi', 'PLU', 'CC', 'RNU'] as $k)@if (! empty($stats['types'][$k]))<div><dt>{{ $label[$k] }}</dt><dd class="num">{{ $stats['types'][$k] }} <small>communes</small></dd></div>@endif @endforeach
            </dl>
            <p style="margin-top:18px">Le document en vigueur décide des seuils et des règles : en zone urbaine d'un PLU, une extension jusqu'à 40 m² passe en déclaration préalable ; avec une carte communale ou le règlement national, le seuil reste à 20 m².</p>
            @if ($stats['plui'])
                <h3 style="margin-top:24px">Les principaux PLU intercommunaux</h3>
                <ul class="mh-list">@foreach (collect($stats['plui'])->take(10) as $name => $n)<li><span>{{ $name }}</span><small>{{ $n }} communes</small></li>@endforeach</ul>
            @endif
        </div>
        <div>
            <span class="eyebrow">Patrimoine et risques</span>
            <h2>Monuments historiques et zones protégées</h2>
            <p>{{ number_format($stats['monuments'], 0, ',', ' ') }} monuments historiques sont protégés {{ $in }}. Autour de chacun, l'Architecte des Bâtiments de France donne son avis sur les projets visibles : l'instruction prend un mois de plus.@if ($stats['ppri']) {{ $stats['ppri'] }} communes ont aussi un plan de prévention du risque inondation.@endif</p>
            @if ($stats['spr']->isNotEmpty())
                <h3 style="margin-top:20px">Sites patrimoniaux remarquables</h3>
                <ul class="near">@foreach ($stats['spr'] as $f)<li>@if ($f->town['page'])<a href="{{ Geo::url($f->town) }}">{{ $f->town['name'] }}</a>@else<span>{{ $f->town['name'] }}</span>@endif</li>@endforeach</ul>
            @endif
            @if ($stats['mh_top']->isNotEmpty())
                <h3 style="margin-top:20px">Les communes les plus riches en monuments</h3>
                <ul class="mh-list">@foreach ($stats['mh_top'] as $f)<li>@if ($f->town['page'])<a href="{{ Geo::url($f->town) }}">{{ $f->town['name'] }}</a>@else<span>{{ $f->town['name'] }}</span>@endif<small>{{ $f->monuments()->count() }} monuments</small></li>@endforeach</ul>
            @endif
        </div>
    </div>
</section>
@endif
<section>
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Communes</span><h2>Les principales communes de {{ $dep['name'] }}, par intercommunalité</h2></div>
        <div class="town-groups">
        @foreach ($byEpci as $epci => $group)
            <div class="epci-group">
                <h3>{{ $epci }}</h3>
                <ul class="town-index">@foreach ($group->sortBy('name') as $t)<li><a href="{{ Geo::url($t) }}">{{ $t['name'] }}</a><small>{{ $t['cp'][0] ?? '' }}</small></li>@endforeach</ul>
            </div>
        @endforeach
        </div>
    </div>
</section>
<section class="stone">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Questions</span><h2>Urbanisme {{ $in }} : vos questions</h2></div>
        <div class="faq">@foreach ($depFaq as [$q, $r])<details><summary>{{ $q }}</summary><p>{{ $r }}</p></details>@endforeach</div>
    </div>
</section>
@include('site.partials.cta')
@endsection
