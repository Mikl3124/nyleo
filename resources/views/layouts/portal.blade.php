@php($company = \App\Models\CompanySetting::current())
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Mon espace') — {{ $company->displayName() }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/nyleo.css') }}?v={{ filemtime(public_path('css/nyleo.css')) }}">
</head>
<body>
<div class="band"><div class="wrap"><span class="label">Plans &amp; permis · Dordogne · Gironde</span>@if ($company->phone)<span>Une question ? <a href="tel:{{ preg_replace('/\s+/', '', $company->phone) }}">{{ $company->phone }}</a></span>@endif</div></div>
<header class="top">
    <div class="wrap">
        <a class="logo" href="{{ auth('client')->check() ? route('portal.home') : route('home') }}">@include('site.partials.logo')<span><b>{{ $company->displayName() }}</b><small>Mon espace client</small></span></a>
        @auth('client')
            <form method="post" action="{{ route('portal.logout') }}">@csrf<button class="btn small secondary" type="submit">Se déconnecter</button></form>
        @endauth
    </div>
</header>
@yield('hero')
<main>
    <div class="wrap">
        @if (session('status'))<div class="flash" role="status">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="errors" role="alert">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        @yield('content')
    </div>
</main>
<footer class="bottom">
    <div class="wrap">
        Une question ? @if ($company->phone)Appelez-moi au <a href="tel:{{ preg_replace('/\s+/', '', $company->phone) }}"><strong>{{ $company->phone }}</strong></a>@endif
        @if ($company->email) ou écrivez à <a href="mailto:{{ $company->email }}">{{ $company->email }}</a>@endif.
        <br><a href="{{ route('home') }}">Site {{ $company->displayName() }}</a> · <a href="{{ route('legal') }}">Mentions légales</a> · <a href="{{ route('privacy') }}">Confidentialité</a>
    </div>
</footer>
</body>
</html>
