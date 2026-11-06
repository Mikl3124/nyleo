@extends('layouts.site')
@section('title', 'Qui suis-je')
@section('description', "Mickaël Delpech, dessinateur basé à Sourzac (Dordogne) : plans de déclaration préalable et de permis de construire pour les particuliers de Dordogne et de Gironde.")
@php
    $base = \App\Support\Geo::base();
    $c = \App\Models\CompanySetting::current();
@endphp
@push('head')
<script type="application/ld+json">{!! json_encode(array_filter([
    '@@context' => 'https://schema.org',
    '@type' => 'Person',
    '@id' => url('/qui-suis-je').'#mickael',
    'name' => 'Mickaël Delpech',
    'jobTitle' => 'Dessinateur en bâtiment',
    'worksFor' => ['@id' => url('/').'#entreprise'],
    'workLocation' => ['@type' => 'Place', 'name' => $base['name'].', Dordogne'],
    'knowsAbout' => ['Déclaration préalable de travaux', 'Permis de construire', "Règles d'urbanisme en Dordogne et en Gironde"],
    'url' => url('/qui-suis-je'),
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('content')
@include('site.partials.page-hero', [
    'crumbs' => 'Qui suis-je', 'kicker' => 'Votre interlocuteur',
    'h1' => 'Mickaël, dessinateur <span class="hl">à '.e($base['name']).'</span>',
    'lead' => "Je dessine les plans et je monte les dossiers d'urbanisme des particuliers, de la véranda à la maison individuelle.",
])
<section>
    <div class="wrap">
        <div style="max-width:820px">
            <h2>Pourquoi faire appel à moi</h2>
            <p class="lead">Un dossier d'urbanisme, c'est une dizaine de pièces, un long formulaire et des règles qui changent d'une commune à l'autre. Mon travail, c'est de vous enlever tout ça.</p>
            <p>Je travaille depuis {{ $base['name'] }}, dans la vallée de l'Isle, entre Périgueux et Libourne. Je connais les règlements d'urbanisme et les services instructeurs de Dordogne et de Gironde, et je sais ce qu'ils attendent. Je reste votre seul interlocuteur du premier appel jusqu'à la décision de la mairie.</p>
            <p>Je travaille avec les particuliers, et aussi avec les artisans et installateurs (vérandas, pergolas, piscines) qui me confient les dossiers de leurs clients. Si la surface de plancher ou l'emprise au sol dépasse 150 m², ou si le projet est porté par une société (SCI…), je travaille avec un architecte partenaire, qui signe le projet.</p>
            <div class="values">
                <div><b>Un seul interlocuteur</b><span>Vous parlez à la personne qui dessine vos plans.</span></div>
                <div><b>Toutes les pièces</b><span>Chaque planche est vérifiée avant le dépôt.</span></div>
                <div><b>Deux versions des plans</b><span>Vous relisez et vous corrigez avant le dépôt.</span></div>
                <div><b>Simple pour tous</b><span>Pas de mot de passe, de gros boutons, et le téléphone pour le reste.</span></div>
            </div>
        </div>
    </div>
</section>
<section class="stone">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow">Méthode</span><h2>Qui fait quoi</h2></div>
        @include('site.partials.lanes')
    </div>
</section>
<section>
    <div class="wrap split" style="align-items:center">
        <div>
            <span class="eyebrow">Entreprise</span>
            <h2>Les informations de l'entreprise</h2>
            <dl class="facts" style="grid-template-columns:1fr 1fr;margin-top:20px">
                <div><dt>Raison sociale</dt><dd>{{ $c->company_name ?: $c->displayName() }}</dd></div>
                <div><dt>Bureau</dt><dd>{{ $c->city ?: $base['name'] }}</dd></div>
                @if ($c->siret)<div><dt>SIRET</dt><dd class="num">{{ $c->siret }}</dd></div>@endif
                @if ($c->phone)<div><dt>Téléphone</dt><dd class="num">{{ $c->phone }}</dd></div>@endif
            </dl>
        </div>
        <div class="map-card">@include('site.partials.map-region')</div>
    </div>
</section>
@include('site.partials.cta')
@endsection
