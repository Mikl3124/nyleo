@php
    $company = \App\Models\CompanySetting::current();
    $tel = $company->phone ? preg_replace('/\s+/', '', $company->phone) : null;
    // Les sections sont déjà échappées par Blade : on les décode une fois pour ne pas afficher « d&#039;Ornon ».
    $title = html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES | ENT_HTML5);
    $fullTitle = $title ? $title.' | '.$company->displayName() : $company->displayName().' — Plans de permis de construire et déclaration préalable en Dordogne et Gironde';
    $description = html_entity_decode(trim($__env->yieldContent('description')), ENT_QUOTES | ENT_HTML5) ?: "Plans et dossiers de déclaration préalable et de permis de construire en Dordogne et en Gironde : dessin, CERFA, dépôt en mairie en votre nom et suivi jusqu'à la décision. Devis gratuit.";
    $nav = [
        'site.dp' => 'Déclaration préalable',
        'site.pc' => 'Permis de construire',
        'site.projects' => 'Projets',
        'site.areas' => 'Secteurs',
        'site.about' => 'Qui suis-je',
        'site.faq' => 'Questions',
    ];
    $base = \App\Support\Geo::base();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="theme-color" content="#1d3f91">
    <meta property="og:image" content="{{ asset('img/og-nyleo.png').'?v=3' }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $company->displayName() }}, plans de permis de construire et de déclaration préalable en Dordogne et Gironde">
    <meta property="og:site_name" content="{{ $company->displayName() }}">
    <meta name="twitter:card" content="summary_large_image">
    @if (trim($__env->yieldContent('robots')))<meta name="robots" content="{{ trim($__env->yieldContent('robots')) }}">@endif
    <meta name="geo.region" content="FR-NAQ">
    <meta name="geo.placename" content="{{ $base['name'] }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preload" href="{{ asset('fonts/site/atkinson-hyperlegible-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/site/archivo-var.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
    <script type="application/ld+json">{!! json_encode(['@@context' => 'https://schema.org', '@graph' => [
        array_filter([
            '@type' => 'ProfessionalService',
            '@id' => url('/').'#entreprise',
            'name' => $company->displayName(),
            'legalName' => $company->company_name ?: null,
            'description' => "Plans et dossiers de déclaration préalable et de permis de construire en Dordogne et en Gironde : dessin, CERFA, dépôt en mairie et suivi jusqu'à la décision.",
            'url' => url('/'),
            'telephone' => $company->phone,
            'email' => $company->email,
            'logo' => asset('img/logo-nyleo.png'),
            'image' => asset('img/og-nyleo.png').'?v=3',
            'founder' => ['@id' => url('/qui-suis-je').'#mickael'],
            'address' => $company->city ? array_filter(['@type' => 'PostalAddress', 'streetAddress' => $company->address, 'postalCode' => $company->postal_code, 'addressLocality' => $company->city, 'addressCountry' => 'FR']) : ['@type' => 'PostalAddress', 'addressLocality' => $base['name'], 'postalCode' => $base['cp'][0] ?? null, 'addressRegion' => 'Dordogne', 'addressCountry' => 'FR'],
            'areaServed' => [['@type' => 'AdministrativeArea', 'name' => 'Dordogne'], ['@type' => 'AdministrativeArea', 'name' => 'Gironde']],
            'knowsAbout' => ['Déclaration préalable de travaux', 'Permis de construire', "Plan local d'urbanisme", 'Plans de maison'],
            'hasOfferCatalog' => ['@type' => 'OfferCatalog', 'name' => "Dossiers d'urbanisme", 'itemListElement' => [
                ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Dossier de déclaration préalable', 'url' => route('site.dp')]],
                ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Dossier de permis de construire', 'url' => route('site.pc')]],
            ]],
        ]),
        ['@type' => 'WebSite', '@id' => url('/').'#site', 'url' => url('/'), 'name' => $company->displayName(), 'inLanguage' => 'fr-FR', 'publisher' => ['@id' => url('/').'#entreprise']],
    ]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @stack('head')
</head>
<body>
<a class="skip" href="#contenu">Aller au contenu</a>
<div class="topbar">
    <div class="wrap">
        <div class="dots"><span>Bureau à {{ $base['name'] }}, Dordogne</span><span>Dordogne et Gironde</span><span>Devis gratuit sous 48 h</span></div>
        @if ($tel)<a href="tel:{{ $tel }}">{{ $company->phone }}</a>@endif
    </div>
</div>
<header class="site-head">
    <div class="wrap">
        <a class="brand" href="{{ route('home') }}" aria-label="{{ $company->displayName() }}, accueil">
            @include('site.partials.logo')
            <span><b>{{ $company->displayName() }}</b><small>Plans &amp; permis · 24 · 33</small></span>
        </a>
        <nav class="nav" aria-label="Menu principal">
            @foreach ($nav as $r => $l)<a href="{{ route($r) }}" @if (request()->routeIs($r) || ($r === 'site.areas' && request()->routeIs('site.area', 'site.town')) || ($r === 'site.projects' && request()->routeIs('site.project'))) aria-current="page" @endif>{{ $l }}</a>@endforeach
        </nav>
        <div class="head-actions">
            @if ($tel)<a class="phone-pill" href="tel:{{ $tel }}"><small>Appelez-moi</small><b>@include('site.partials.icon', ['name' => 'phone']) {{ $company->phone }}</b></a>@endif
            <a class="btn cta sm" href="{{ route('site.contact') }}">Devis gratuit</a>
        </div>
        <details class="menu">
            <summary>Menu</summary>
            <div class="menu-panel">
                @foreach ($nav as $r => $l)<a href="{{ route($r) }}">{{ $l }}</a>@endforeach
                <a href="{{ route('site.contact') }}">Contact</a>
                <a href="{{ route('portal.login') }}">Espace client</a>
                @if ($tel)<a class="btn cta" href="tel:{{ $tel }}">@include('site.partials.icon', ['name' => 'phone']) Appeler le {{ $company->phone }}</a>@endif
            </div>
        </details>
    </div>
</header>

<main id="contenu">
    @yield('content')
</main>

<footer class="site-foot">
    <div class="wrap">
        <div class="foot-grid">
            <div>
                <a class="brand" href="{{ route('home') }}">@include('site.partials.logo')<span><b>{{ $company->displayName() }}</b><small>Plans &amp; permis · 24 · 33</small></span></a>
                <p style="margin-top:18px;max-width:24em">Plans et dossiers de déclaration préalable et de permis de construire, dessinés depuis {{ $base['name'] }}, en Dordogne, pour les particuliers de Dordogne et de Gironde.</p>
                @if ($tel)<p><a href="tel:{{ $tel }}" style="font-family:var(--display);font-stretch:115%;font-weight:800;font-size:1.4rem;color:#fff">{{ $company->phone }}</a></p>@endif
            </div>
            <div>
                <h4>Prestations</h4>
                <ul>
                    <li><a href="{{ route('site.dp') }}">Déclaration préalable</a></li>
                    <li><a href="{{ route('site.pc') }}">Permis de construire</a></li>
                    <li><a href="{{ route('site.faq') }}">Questions fréquentes</a></li>
                    <li><a href="{{ route('site.about') }}">Qui suis-je</a></li>
                </ul>
                <h4 style="margin-top:22px">Projets</h4>
                <ul>
                    @foreach (\App\Support\ProjectGuides::all() as $g)<li><a href="{{ route('site.project', $g['slug']) }}">{{ $g['name'] }}</a></li>@endforeach
                </ul>
            </div>
            <div>
                <h4>Dordogne</h4>
                <div class="foot-towns">@foreach (\App\Support\Geo::pages('dordogne')->take(10) as $t)<a href="{{ \App\Support\Geo::url($t) }}">{{ $t['name'] }}</a>@endforeach<a href="{{ route('site.area', 'dordogne') }}"><b>Toutes</b></a></div>
            </div>
            <div>
                <h4>Gironde</h4>
                <div class="foot-towns">@foreach (\App\Support\Geo::pages('gironde')->take(10) as $t)<a href="{{ \App\Support\Geo::url($t) }}">{{ $t['name'] }}</a>@endforeach<a href="{{ route('site.area', 'gironde') }}"><b>Toutes</b></a></div>
            </div>
        </div>
        <div class="foot-bottom">
            <span>© {{ now()->year }} {{ $company->company_name ?: $company->displayName() }}@if ($company->siret) · SIRET {{ $company->siret }}@endif</span>
            <span><a href="{{ route('portal.login') }}">Espace client</a> · <a href="{{ route('legal') }}">Mentions légales</a> · <a href="{{ route('privacy') }}">Confidentialité</a> · <a href="{{ $company->cgvUrl() }}">CGV</a></span>
        </div>
    </div>
</footer>

<div class="callbar">
    @if ($tel)<a class="btn" href="tel:{{ $tel }}">@include('site.partials.icon', ['name' => 'phone']) Appeler</a>@endif
    <a class="btn cta" href="{{ route('site.contact') }}">Devis gratuit</a>
</div>
</body>
</html>
