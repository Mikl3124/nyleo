@extends('layouts.site')
@php
    use App\Support\Geo;
    $base = Geo::base();
    $dep = Geo::department($town['dep']);
    $g = \App\Support\TownGuide::for($town);
    $facts = $g->facts;
    $km = Geo::distance($base, $town);
    $isBase = $town['code'] === $base['code'];
    $cp = implode(', ', $town['cp']);
    $a = Geo::a($town['name']);
    $de = Geo::de($town['name']);
    $c = \App\Models\CompanySetting::current();
    $tel = $c->phone ? preg_replace('/\s+/', '', $c->phone) : null;
    $m = $facts->mairie();
    $heritage = $g->dpHeritage();
    $monuments = $facts->monuments();
    $risks = $g->dpRisks();
    $abfTips = $g->dpAbfTips();
    $faq = $g->dpFaq();
    $summary = $g->summary('dp');
@endphp
@section('title')Déclaration préalable de travaux {{ $a }} ({{ $town['cp'][0] ?? $dep['code'] }})@endsection
@section('description')Déclaration préalable {{ $a }} ({{ $cp }}) : {{ $summary }}. Véranda, abri, piscine, clôture, façade : plans DP1 à DP8 et CERFA déposés en mairie pour vous.@endsection
@include('site.partials.town-ld', ['service' => 'Dossier de déclaration préalable de travaux'])
@section('content')
@include('site.partials.page-hero', [
    'crumbs' => ['Déclaration préalable' => route('site.dp'), $dep['name'] => Geo::areaUrl($town['dep'], 'dp'), $town['name']],
    'kicker' => 'Déclaration préalable · '.$town['name'].' · '.$cp,
    'h1' => 'Déclaration préalable de travaux <span class="hl">'.e($a).'</span>',
    'lead' => $g->dpLead(),
    'cta' => ($tel ? '<a class="btn cta" href="tel:'.e($tel).'">Appeler le '.e($c->phone).'</a>' : '').'<a class="btn ghost" href="'.route('site.contact', ['demarche' => 'DP', 'commune' => $town['name'].' ('.($town['cp'][0] ?? '').')']).'">Devis gratuit</a>',
])
<section>
    <div class="wrap town-hero">
        <div>
            <dl class="facts">
                <div><dt>Extension en DP</dt><dd>{{ $g->dpThreshold() }}</dd></div>
                <div><dt>Délai</dt><dd class="num">{{ $g->dpDelay() }}</dd></div>
                <div><dt>Avis de l'ABF</dt><dd>{{ match ($g->abf()) { true => $g->spr() ? 'Site protégé' : 'Près des monuments', false => 'Rarement', null => 'À vérifier' } }}</dd></div>
                <div><dt>Depuis le bureau</dt><dd class="num">{{ $isBase ? 'Sur place' : $km.' km' }}</dd></div>
            </dl>
            <div style="margin-top:28px">
                <h2>Ce qui compte pour une déclaration {{ $a }}</h2>
                @foreach ($g->dpRules() as $p)<p>{{ $p }}</p>@endforeach
            </div>
        </div>
        <figure class="map-card" style="margin:0">
            @include('site.partials.map-commune', ['town' => $town, 'kind' => 'dp'])
            <figcaption class="legend"><span><i style="background:var(--blue)"></i>{{ $town['name'] }}</span><span><i style="background:#c9d6f2"></i>Communes voisines avec leur page</span>@if (! $isBase && $km <= 18)<span><i style="background:var(--ochre);border:1px solid var(--ink)"></i>Le bureau</span>@endif</figcaption>
        </figure>
    </div>
</section>
<section class="stone">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Vos travaux</span><h2>Déclaration préalable ou pas ? Les cas courants {{ $a }}</h2></div>
        <div class="table-wrap">
            <table class="delays works">
                <thead><tr><th scope="col">Travaux</th><th scope="col">Démarche</th><th scope="col">{{ Str::ucfirst($a) }}</th></tr></thead>
                <tbody>@foreach ($g->dpWorks() as [$what, $proc, $detail])<tr><th scope="row">{{ $what }}</th><td>{{ $proc }}</td><td>{{ $detail }}</td></tr>@endforeach</tbody>
            </table>
        </div>
        <p class="muted" style="margin-top:12px">Les surfaces comptent l'emprise au sol ou la surface de plancher créée, la plus grande des deux. Un doute ? Le simulateur plus bas, ou un appel, et je vous réponds.</p>
    </div>
</section>
@if ($heritage !== null || $risks)
<section>
    <div class="wrap split" style="align-items:start">
        @if ($heritage !== null)
        <div>
            <span class="eyebrow">Patrimoine</span>
            <h2>L'Architecte des Bâtiments de France {{ $a }}</h2>
            @foreach ($heritage as $p)<p>{{ $p }}</p>@endforeach
            @if ($monuments->isNotEmpty())
                <ul class="mh-list">
                    @foreach ($monuments->take(10) as $mh)<li><span>{{ $mh['n'] }}</span><small>{{ $mh['p'] }}</small></li>@endforeach
                    @if ($monuments->count() > 10)<li class="muted">et {{ $monuments->count() - 10 }} autres</li>@endif
                </ul>
            @endif
        </div>
        @endif
        <div>
            @if ($abfTips)
                <span class="eyebrow">Mon conseil</span>
                <h2>Un dossier qui passe chez l'ABF</h2>
                <ul class="ticks-list" style="margin-top:12px">@foreach ($abfTips as [$t, $d])<li>@include('site.partials.icon', ['name' => 'check'])<span><b>{{ $t }}</b><span>{{ $d }}</span></span></li>@endforeach</ul>
            @endif
            @if ($risks)
                <h3 style="margin-top:{{ $abfTips ? '28px' : '0' }}">Les risques qui touchent aussi les petits travaux</h3>
                <ul class="ticks-list" style="margin-top:12px">@foreach ($risks as [$t, $d])<li>@include('site.partials.icon', ['name' => 'check'])<span><b>{{ $t }}</b><span>{{ $d }}</span></span></li>@endforeach</ul>
            @endif
        </div>
    </div>
</section>
@endif
<section class="{{ $heritage !== null || $risks ? 'stone' : '' }}">
    <div class="wrap split" style="align-items:start">
        <div>
            <span class="eyebrow">Le dossier</span>
            <h2>Les pièces de votre déclaration</h2>
            <p>Pour une maison individuelle, le formulaire est le CERFA 13703. Je prépare les pièces utiles à votre projet, ni plus ni moins :</p>
            <ul class="pieces">@foreach (\App\Support\SiteContent::DP_PIECES as [$code, $name, $desc])<li><b>{{ $code }}</b><span>{{ $name }}</span><small>{{ $desc }}</small></li>@endforeach</ul>
        </div>
        <div>
            <span class="eyebrow">Après le dépôt</span>
            <h2>De la mairie aux travaux</h2>
            <ul class="ticks-list" style="margin-top:12px">
                <li>@include('site.partials.icon', ['name' => 'check'])<span><b>Dépôt à la mairie {{ $de }}</b><span>En ligne ou au guichet, en votre nom. Vous recevez le récépissé avec la date de fin d'instruction.</span></span></li>
                <li>@include('site.partials.icon', ['name' => 'check'])<span><b>{{ $g->dpDelay() }} d'instruction</b><span>La mairie a un mois pour réclamer une pièce : je la fournis. Sans réponse à la date prévue, la déclaration est acceptée.</span></span></li>
                <li>@include('site.partials.icon', ['name' => 'check'])<span><b>Panneau et recours</b><span>Le panneau reste affiché pendant les travaux. Les tiers ont deux mois pour contester.</span></span></li>
                <li>@include('site.partials.icon', ['name' => 'check'])<span><b>Trois ans pour réaliser</b><span>L'accord vaut trois ans, prolongeable deux fois d'un an. La fin des travaux se déclare en mairie.</span></span></li>
            </ul>
            @if ($m && ! empty($m['adresse']))
                <div class="mairie-card">
                    <span class="eyebrow">Où le dossier est déposé</span>
                    <p><b>Mairie {{ $de }}</b><br>{{ $m['adresse'] }}<br>{{ $m['cp'] }} {{ $m['ville'] }}@if (! empty($m['telephone']))<br>{{ $m['telephone'] }}@endif</p>
                </div>
            @endif
        </div>
    </div>
</section>
<section>
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Simulateur</span><h2>Votre projet {{ $a }} demande-t-il une déclaration ?</h2></div>
        @include('site.partials.simulator')
        <h3 style="font-size:1.1rem;margin-top:28px">Les guides des petits travaux</h3>
        <ul class="near">@foreach (\App\Support\TownGuide::guides('dp') as $guide)<li><a href="{{ route('site.project', $guide['slug']) }}">{{ $guide['name'] }}</a></li>@endforeach</ul>
        <p style="margin-top:22px">Une maison neuve ou une extension plus grande ? <a class="link-arrow" href="{{ Geo::url($town, 'pc') }}">Le permis de construire {{ $a }} @include('site.partials.icon', ['name' => 'arrow'])</a></p>
    </div>
</section>
@include('site.partials.town-bottom', ['kind' => 'dp'])
@endsection
