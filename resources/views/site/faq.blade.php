@extends('layouts.site')
@section('title', 'Questions fréquentes sur la déclaration préalable et le permis de construire')
@section('description', "Seuils, délais d'instruction, architecte, taxe d'aménagement, RE2020, affichage : les réponses aux questions les plus fréquentes sur vos démarches d'urbanisme.")
@push('head')
<script type="application/ld+json">{!! json_encode(['@@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => collect(\App\Support\SiteContent::FAQ)->map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]])->all()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('content')
@include('site.partials.page-hero', [
    'crumbs' => 'Questions fréquentes', 'kicker' => 'Vos questions',
    'h1' => 'Les réponses <span class="hl">simples</span> à vos questions',
    'lead' => "Une question qui n'est pas ici ? Appelez-moi, c'est le plus simple.",
])
<section>
    <div class="wrap">
        <div class="faq">@foreach (\App\Support\SiteContent::FAQ as [$q, $a])<details><summary>{{ $q }}</summary><p>{{ $a }}</p></details>@endforeach</div>
    </div>
</section>
@include('site.partials.cta')
@endsection
