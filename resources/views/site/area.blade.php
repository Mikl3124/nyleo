@extends('layouts.site')
@php
    use App\Support\Geo;
    $base = Geo::base();
    $pages = Geo::pages($slug);
    $all = Geo::communes($slug);
    $pop = $all->sum('pop');
@endphp
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
<section class="stone">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Communes</span><h2>Les principales communes de {{ $dep['name'] }}</h2></div>
        <ul class="town-index">@foreach ($pages->sortBy('name') as $t)<li><a href="{{ Geo::url($t) }}">{{ $t['name'] }}</a><small>{{ $t['cp'][0] ?? '' }}</small></li>@endforeach</ul>
    </div>
</section>
@include('site.partials.cta')
@endsection
