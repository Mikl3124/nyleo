{{-- Pages d'erreur : même charte que le site et l'espace client. Aucune dépendance obligatoire à la base de données. --}}
@php
    $company = rescue(fn () => \App\Models\CompanySetting::current(), null, false);
    $phone = $company?->phone;
    $tel = $phone ? preg_replace('/\s+/', '', $phone) : null;
    $css = public_path('css/nyleo.css');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') — Nyleo</title>
    <link rel="icon" href="{{ url('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ url('css/nyleo.css') }}?v={{ is_file($css) ? filemtime($css) : 1 }}">
</head>
<body>
<div class="band"><div class="wrap"><span class="label">Plans &amp; permis · Dordogne · Gironde</span>@if ($tel)<span>Une question ? <a href="tel:{{ $tel }}">{{ $phone }}</a></span>@endif</div></div>
<header class="top">
    <div class="wrap">
        <a class="logo" href="{{ url('/') }}">@include('site.partials.logo')<span><b>nyleo</b><small>Plans &amp; permis · 24 · 33</small></span></a>
    </div>
</header>
<main>
    <div class="wrap">
        <div class="card ruled error-card">
            <p class="error-code">Erreur @yield('code')</p>
            <h1>@yield('title')</h1>
            <p class="error-lead">@yield('message')</p>
            <div class="error-actions">@yield('actions')</div>
        </div>
        @if ($tel)
            <p class="error-help">Besoin d'aide ? Appelez-moi au <a href="tel:{{ $tel }}"><strong>{{ $phone }}</strong></a>, je vous réponds directement.</p>
        @endif
    </div>
</main>
<footer class="bottom">
    <div class="wrap"><a href="{{ url('/') }}">Site Nyleo</a> · <a href="{{ url('/espace/connexion') }}">Mon espace client</a> · <a href="{{ url('/mentions-legales') }}">Mentions légales</a></div>
</footer>
</body>
</html>
