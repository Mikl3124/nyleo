@extends('layouts.site')
@php
    use App\Support\Geo;
    $base = Geo::base();
    $all = Geo::communes($slug);
    $pop = $all->sum('pop');
    $stats = \App\Support\TownFacts::department($slug);
    $label = ['PLUi' => 'PLU intercommunal', 'PLU' => 'PLU communal', 'CC' => 'carte communale', 'RNU' => 'règlement national (RNU)'];
    $in = Geo::inDep($slug);
    $of = Geo::ofDep($slug);
    $depFaq = array_values(array_filter([
        $stats ? ["Quelles communes ont un PLU {$in} ?", collect(['PLUi', 'PLU', 'CC', 'RNU'])->filter(fn ($k) => ! empty($stats['types'][$k]))->map(fn ($k) => $stats['types'][$k].' '.($stats['types'][$k] > 1 ? 'communes' : 'commune').' '.($k === 'RNU' ? 'relèvent du' : 'ont un').' '.$label[$k])->implode(', ').". Le type de document change les seuils : sans PLU, une extension de plus de 20 m² demande un permis de construire." ] : null,
        ["Combien de temps pour obtenir un permis de construire {$in} ?", "Deux mois pour une maison individuelle ou son extension, trois mois avec l'avis de l'Architecte des Bâtiments de France. Les autres permis demandent trois mois. Le délai court à partir du dossier complet."],
        ["Peut-on construire une maison hors d'un PLU {$in} ?", "Avec une carte communale, oui dans ses secteurs constructibles. Sous le règlement national d'urbanisme, en principe seulement dans les parties déjà urbanisées de la commune. Un certificat d'urbanisme opérationnel, demandé avant l'achat du terrain, dit si la maison est possible."],
        ["Intervenez-vous dans {$dep['all']} ?", "Oui. Mon bureau est à {$base['name']} (24) et je travaille à distance pour les {$all->count()} communes du département : un appel, vos photos et le cadastre suffisent dans la plupart des cas."],
    ]));
@endphp
@push('head')
<script type="application/ld+json">{!! json_encode(['@@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => collect($depFaq)->map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]])->all()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('title', 'Permis de construire '.$in.' ('.$dep['code'].') : maison, extension')
@section('description', "Permis de construire {$in} : maison neuve, extension, grange. Documents d'urbanisme, risques et délais des {$all->count()} communes {$of}, dossier préparé par un dessinateur basé à {$base['name']}.")
@section('content')
@include('site.partials.page-hero', [
    'crumbs' => ['Permis de construire' => route('site.pc'), $dep['name']], 'kicker' => 'Permis de construire · '.$dep['name'].' ('.$dep['code'].')',
    'h1' => 'Permis de construire <span class="hl">'.e($in).'</span>',
    'lead' => $content['intro'],
    'cta' => '<a class="btn cta" href="'.route('site.contact', ['demarche' => 'PC']).'">Demander un devis</a>',
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
            <p class="muted">Choisissez votre commune : sa page dit où et comment on peut y construire. J'interviens aussi dans toutes les autres.</p>
            @include('site.partials.town-finder', ['slug' => $slug, 'kind' => 'pc'])
            <h3 style="margin-top:32px">Bon à savoir {{ $in }}</h3>
            <ul class="ticks-list">@foreach ($content['tips'] as [$t, $d])<li>@include('site.partials.icon', ['name' => 'check'])<span><b>{{ $t }}</b><span>{{ $d }}</span></span></li>@endforeach</ul>
        </div>
        <div class="map-card">
            @include('site.partials.map-department', ['slug' => $slug, 'kind' => 'pc'])
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
            <p style="margin-top:18px">Le document en vigueur dit où une maison neuve est possible : dans les zones U et AU d'un PLU, dans les secteurs constructibles d'une carte communale, et seulement dans les parties déjà urbanisées sous le règlement national. Il fixe aussi l'emprise au sol, la hauteur et l'aspect.</p>
            @if ($stats['plui'])
                <h3 style="margin-top:24px">Les principaux PLU intercommunaux</h3>
                <ul class="mh-list">@foreach (collect($stats['plui'])->take(10) as $name => $n)<li><span>{{ $name }}</span><small>{{ $n }} communes</small></li>@endforeach</ul>
            @endif
        </div>
        <div>
            <span class="eyebrow">Risques et patrimoine</span>
            <h2>Ce qui encadre une construction neuve</h2>
            <p>@if ($stats['ppri']){{ $stats['ppri'] }} communes {{ $of }} ont un plan de prévention du risque inondation : en zone rouge, les maisons neuves sont en général interdites, en zone bleue le plancher doit être surélevé. @endif Dans les abords des {{ number_format($stats['monuments'], 0, ',', ' ') }} monuments historiques du département, le permis d'une maison est instruit en trois mois au lieu de deux.@php($rate = \App\Support\TownFacts::departmentHousingRate($slug))@if ($rate) On y autorise en moyenne {{ number_format($rate, 1, ',', ' ') }} logements par an pour 1 000 habitants (base Sitadel).@endif</p>
        </div>
    </div>
</section>
@endif
@include('site.partials.area-towns', ['kind' => 'pc'])
<section class="stone">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Questions</span><h2>Permis de construire {{ $in }} : vos questions</h2></div>
        <div class="faq">@foreach ($depFaq as [$q, $r])<details><summary>{{ $q }}</summary><p>{{ $r }}</p></details>@endforeach</div>
        <p style="margin-top:22px">Des travaux plus modestes : véranda, abri, piscine, façade ? <a class="link-arrow" href="{{ Geo::areaUrl($slug, 'dp') }}">La déclaration préalable {{ $in }} @include('site.partials.icon', ['name' => 'arrow'])</a></p>
    </div>
</section>
@include('site.partials.cta')
@endsection
