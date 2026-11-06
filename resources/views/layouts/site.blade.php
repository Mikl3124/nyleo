@php
    $company = \App\Models\CompanySetting::current();
    $tel = $company->phone ? preg_replace('/\s+/', '', $company->phone) : null;
    $title = trim($__env->yieldContent('title'));
    $fullTitle = $title ? $title.' | '.$company->displayName() : $company->displayName().' — Plans de permis de construire et déclaration préalable en Dordogne et Gironde';
    $description = trim($__env->yieldContent('description')) ?: "Plans et dossiers de déclaration préalable et de permis de construire en Dordogne et en Gironde : dessin, CERFA, dépôt en mairie en votre nom et suivi jusqu'à la décision. Devis gratuit.";
    $nav = [
        'site.dp' => 'Déclaration préalable',
        'site.pc' => 'Permis de construire',
        'site.pricing' => 'Tarifs',
        'site.faq' => 'Questions',
        'site.contact' => 'Contact',
    ];
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
    <meta name="theme-color" content="#1e3447">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preload" href="{{ asset('fonts/site/inter-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/site/fraunces-latin-600-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
    <script type="application/ld+json">{!! json_encode(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'ProfessionalService',
        'name' => $company->displayName(),
        'description' => $description,
        'url' => url('/'),
        'telephone' => $company->phone,
        'email' => $company->email,
        'address' => $company->city ? ['@type' => 'PostalAddress', 'streetAddress' => $company->address, 'postalCode' => $company->postal_code, 'addressLocality' => $company->city, 'addressCountry' => 'FR'] : null,
        'areaServed' => [['@type' => 'AdministrativeArea', 'name' => 'Dordogne'], ['@type' => 'AdministrativeArea', 'name' => 'Gironde']],
        'serviceType' => ['Déclaration préalable de travaux', 'Permis de construire', 'Plans de maison'],
    ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @stack('head')
</head>
<body>
<a class="skip" href="#contenu">Aller au contenu</a>
<header class="site-head">
    <div class="wrap">
        <a class="brand" href="{{ route('home') }}" aria-label="{{ $company->displayName() }}, accueil">
            @include('site.partials.logo')
            <span><b>{{ $company->displayName() }}</b><small>Plans · DP · Permis de construire</small></span>
        </a>
        <nav class="nav" aria-label="Menu principal">
            @foreach ($nav as $r => $l)<a href="{{ route($r) }}" @if (request()->routeIs($r)) aria-current="page" @endif>{{ $l }}</a>@endforeach
        </nav>
        <div class="head-actions">
            @if ($tel)<a class="phone" href="tel:{{ $tel }}">@include('site.partials.icon', ['name' => 'phone']) {{ $company->phone }}</a>@endif
            <a class="btn sm ghost" href="{{ route('portal.login') }}">@include('site.partials.icon', ['name' => 'user']) Espace client</a>
        </div>
        <details class="menu">
            <summary>Menu</summary>
            <div class="menu-panel">
                @foreach ($nav as $r => $l)<a href="{{ route($r) }}">{{ $l }}</a>@endforeach
                <a href="{{ route('portal.login') }}">Espace client</a>
                @if ($tel)<a class="btn green" href="tel:{{ $tel }}">@include('site.partials.icon', ['name' => 'phone']) Appeler le {{ $company->phone }}</a>@endif
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
                <a class="brand" href="{{ route('home') }}">@include('site.partials.logo')<span><b>{{ $company->displayName() }}</b><small>Plans · DP · Permis de construire</small></span></a>
                <p style="margin-top:18px;max-width:22em">Plans et dossiers d'urbanisme pour les particuliers, de la première esquisse à la décision de la mairie.</p>
            </div>
            <div>
                <h4>Prestations</h4>
                <ul>
                    <li><a href="{{ route('site.dp') }}">Déclaration préalable</a></li>
                    <li><a href="{{ route('site.pc') }}">Permis de construire</a></li>
                    <li><a href="{{ route('site.pricing') }}">Tarifs</a></li>
                    <li><a href="{{ route('site.faq') }}">Questions fréquentes</a></li>
                </ul>
            </div>
            <div>
                <h4>Secteurs</h4>
                <ul>
                    <li><a href="{{ route('site.area', 'dordogne') }}">Dordogne (24)</a></li>
                    <li><a href="{{ route('site.area', 'gironde') }}">Gironde (33)</a></li>
                </ul>
            </div>
            <div>
                <h4>Contact</h4>
                <ul>
                    @if ($tel)<li><a href="tel:{{ $tel }}">{{ $company->phone }}</a></li>@endif
                    @if ($company->email)<li><a href="mailto:{{ $company->email }}">{{ $company->email }}</a></li>@endif
                    <li><a href="{{ route('site.contact') }}">Demander un devis</a></li>
                    <li><a href="{{ route('portal.login') }}">Espace client</a></li>
                </ul>
            </div>
        </div>
        <div class="foot-bottom">
            <span>© {{ now()->year }} {{ $company->company_name ?: $company->displayName() }}</span>
            <span><a href="{{ route('legal') }}">Mentions légales</a> · <a href="{{ route('privacy') }}">Confidentialité</a>@if ($company->cgv_url ?? null) · <a href="{{ $company->cgv_url }}">CGV</a>@endif</span>
        </div>
    </div>
</footer>

<div class="callbar">
    @if ($tel)<a class="btn" href="tel:{{ $tel }}">@include('site.partials.icon', ['name' => 'phone']) Appeler</a>@endif
    <a class="btn green" href="{{ route('site.contact') }}">Devis gratuit</a>
</div>
</body>
</html>
