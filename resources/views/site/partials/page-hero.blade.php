<section class="page-hero">
    <div class="wrap">
        <nav class="crumbs" aria-label="Fil d'Ariane"><a href="{{ route('home') }}">Accueil</a> / {{ $crumb }}</nav>
        <span class="kicker">{{ $kicker }}</span>
        <h1>{!! $h1 !!}</h1>
        <p class="lead">{{ $lead }}</p>
        @isset($cta)<div class="hero-cta" style="margin-bottom:0">{!! $cta !!}</div>@endisset
    </div>
</section>
