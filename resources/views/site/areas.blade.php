@extends('layouts.site')
@section('title', 'Secteurs : Dordogne et départements voisins')
@section('description', "Plans de déclaration préalable et de permis de construire dans toutes les communes de Dordogne et des départements voisins, depuis un bureau à Sourzac (24).")
@php
    $base = \App\Support\Geo::base();
@endphp
@section('content')
@include('site.partials.page-hero', [
    'crumbs' => 'Secteurs', 'kicker' => '24 · 33 · 16 · 17 · 87 · 19 · 46 · 47',
    'h1' => 'Un dessinateur <span class="hl">local</span>, autour de la Dordogne',
    'lead' => "Mon bureau est à {$base['name']}, au cœur de la vallée de l'Isle. De là, je travaille pour toutes les communes de la Dordogne et de ses six départements voisins.",
])
<section>
    <div class="wrap split" style="align-items:center">
        <div>
            <span class="eyebrow">Carte</span>
            <h2>Où j'interviens</h2>
            <p class="lead">Tout se fait à distance : un appel, vos photos et le cadastre suffisent dans la plupart des cas. Si une visite est utile, je vous le dis avant le devis.</p>
            @include('site.partials.town-finder')
        </div>
        <div class="map-card">@include('site.partials.map-region')</div>
    </div>
</section>
<section class="stone">
    <div class="wrap">@include('site.partials.dep-cards')</div>
</section>
@include('site.partials.cta')
@endsection
