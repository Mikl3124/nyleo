@extends('layouts.site')
@php
    use App\Support\Geo;
    use App\Support\ProjectGuides;
    $c = \App\Models\CompanySetting::current();
    $tel = $c->phone ? preg_replace('/\s+/', '', $c->phone) : null;
    $fmt = fn ($p) => $p ? number_format($p, 0, ',', ' ').' €' : null;
    $procs = $guide['proc'] === 'DP ou PC' ? ['DP', 'PC'] : [$guide['proc']];
    $labels = ['DP' => 'Déclaration préalable', 'PC' => 'Permis de construire'];
    $demarche = count($procs) === 1 ? $procs[0] : 'AUTRE';
    $offers = collect($procs)->filter(fn ($p) => ! empty($prices[$p]))->map(fn ($p) => [
        '@type' => 'Offer', 'name' => $labels[$p].' : '.mb_strtolower($guide['name']), 'priceCurrency' => 'EUR',
        'priceSpecification' => ['@type' => 'PriceSpecification', 'minPrice' => $prices[$p], 'priceCurrency' => 'EUR'],
    ])->values()->all();
    $towns = Geo::pages()->sortByDesc('pop')->take(12)->sortBy('name');
@endphp
@section('title', $guide['title'])
@section('description', $guide['description'])
@push('head')
<script type="application/ld+json">{!! json_encode([
    '@@context' => 'https://schema.org',
    '@graph' => [
        array_filter([
            '@type' => 'Service',
            'name' => strip_tags($guide['h1']),
            'description' => $guide['description'],
            'serviceType' => count($procs) === 1 ? $labels[$procs[0]] : 'Déclaration préalable ou permis de construire',
            'provider' => ['@id' => url('/').'#entreprise'],
            'areaServed' => [['@type' => 'AdministrativeArea', 'name' => 'Dordogne'], ['@type' => 'AdministrativeArea', 'name' => 'Gironde']],
            'url' => url()->current(),
            'offers' => $offers ?: null,
        ]),
        ['@type' => 'FAQPage', 'mainEntity' => collect($guide['faq'])->map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]])->all()],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('content')
@include('site.partials.page-hero', [
    'crumbs' => ['Vos projets' => route('site.projects'), $guide['name']],
    'kicker' => $guide['name'].' · Dordogne et Gironde',
    'h1' => $guide['h1'],
    'lead' => $guide['lead'],
    'cta' => ($tel ? '<a class="btn cta" href="tel:'.e($tel).'">Appeler le '.e($c->phone).'</a>' : '').'<a class="btn ghost" href="'.route('site.contact', ['demarche' => $demarche]).'">Devis gratuit</a>',
])
<section>
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">La règle en bref</span><h2>Quelle démarche selon votre cas ?</h2></div>
        <div class="table-wrap">
            <table class="delays rules">
                <thead><tr><th scope="col">Votre cas</th><th scope="col">Démarche</th></tr></thead>
                <tbody>@foreach ($guide['rules'] as [$case, $proc])<tr><th scope="row">{{ $case }}</th><td>{{ $proc }}</td></tr>@endforeach</tbody>
            </table>
        </div>
        <p class="muted" style="margin-top:12px">Règles générales du Code de l'urbanisme. Le règlement de votre zone peut être plus strict : je le vérifie pour votre terrain avant le devis.</p>
        <div class="offer-boxes">
            @foreach ($procs as $p)
                <div class="box{{ $loop->first ? ' ochre' : '' }}"><span class="muted">{{ $labels[$p] }}, dossier complet</span><div class="price"><small>à partir de</small><b>{{ $fmt($prices[$p] ?? null) ?? 'Sur devis' }}</b></div><a class="link-arrow" href="{{ route($p === 'DP' ? 'site.dp' : 'site.pc') }}" style="margin-top:8px">Le dossier en détail @include('site.partials.icon', ['name' => 'arrow'])</a></div>
            @endforeach
            <div class="box"><b>Un doute sur la démarche ?</b><p class="muted" style="margin:6px 0 14px">Cinq minutes au téléphone suffisent pour vous répondre, gratuitement.</p>@if ($tel)<a class="btn cta sm" href="tel:{{ $tel }}">@include('site.partials.icon', ['name' => 'phone']) {{ $c->phone }}</a>@else<a class="btn cta sm" href="{{ route('site.contact', ['demarche' => $demarche]) }}">Me décrire mon projet</a>@endif</div>
        </div>
    </div>
</section>
@foreach ($guide['sections'] as [$h, $paras])
<section class="{{ $loop->odd ? 'stone' : '' }} tight">
    <div class="wrap"><div class="prose">
        <h2>{{ $h }}</h2>
        @foreach ($paras as $p)<p>{{ $p }}</p>@endforeach
    </div></div>
</section>
@endforeach
<section>
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Simulateur</span><h2>Vérifiez votre cas en 10 secondes</h2></div>
        @include('site.partials.simulator', ['prices' => $prices, 'preset' => $guide['sim']])
    </div>
</section>
<section class="stone">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Questions</span><h2>Vos questions</h2></div>
        <div class="faq">@foreach ($guide['faq'] as [$q, $a])<details><summary>{{ $q }}</summary><p>{{ $a }}</p></details>@endforeach</div>
        <p style="margin-top:22px"><a class="link-arrow" href="{{ route('site.faq') }}">Toutes les questions fréquentes @include('site.partials.icon', ['name' => 'arrow'])</a></p>
    </div>
</section>
<section>
    <div class="wrap">
        <div class="sec-head row"><div><span class="eyebrow">Autres projets</span><h2>Vous avez un autre projet ?</h2></div><a class="link-arrow" href="{{ route('site.projects') }}">Tous les projets @include('site.partials.icon', ['name' => 'arrow'])</a></div>
        @include('site.partials.project-cards', ['guides' => ProjectGuides::others($guide['slug'], 3)])
        <h3 style="font-size:1.1rem;margin-top:36px">{{ $guide['name'] }} en Dordogne et en Gironde</h3>
        <ul class="near">@foreach ($towns as $t)<li><a href="{{ Geo::url($t) }}">{{ $t['name'] }}</a></li>@endforeach<li><a href="{{ route('site.areas') }}">Toutes les communes</a></li></ul>
    </div>
</section>
@include('site.partials.cta')
@endsection
