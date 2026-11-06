<section class="page-hero">
    <div class="wrap">
        <nav class="crumbs" aria-label="Fil d'Ariane"><a href="{{ route('home') }}">Accueil</a>@foreach ((array) ($crumbs ?? [$crumb ?? '']) as $label => $url)<span aria-hidden="true">/</span>@if (is_string($label))<a href="{{ $url }}">{{ $label }}</a>@else<span>{{ $url }}</span>@endif @endforeach</nav>
        <span class="eyebrow">{{ $kicker }}</span>
        <h1>{!! $h1 !!}</h1>
        <p class="lead">{{ $lead }}</p>
        @isset($cta)<div class="hero-cta" style="margin-bottom:0">{!! $cta !!}</div>@endisset
    </div>
</section>
