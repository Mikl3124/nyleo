@extends('layouts.site')
@section('title', 'Demande bien reçue')
@section('robots', 'noindex')
@section('no_callbar', '1')
@php
    $c = \App\Models\CompanySetting::current();
    $natures = [
        'veranda' => ['Véranda', 'veranda', 'veranda'], 'extension' => ['Extension, garage', 'extension', 'extension-maison'],
        'maison' => ['Maison neuve', 'maison', 'maison-individuelle'], 'piscine' => ['Piscine', 'piscine', 'piscine'],
        'pergola' => ['Pergola, carport, abri', 'pergola', 'abri-de-jardin-carport-pergola'], 'facade' => ['Façade, fenêtres', 'facade', 'facade-fenetres-toiture'],
        'photovoltaique' => ['Panneaux solaires', 'solaire', 'panneaux-solaires'], 'cloture' => ['Clôture, portail', 'cloture', 'cloture-portail'],
    ];
    [$natureLabel, $natureIcon, $guide] = $natures[$d['nature'] ?? ''] ?? ['Autre projet', 'home', null];
    $guideTitle = $guide ? (\App\Support\ProjectGuides::find($guide)['title'] ?? null) : null;
    $procedure = ['DP' => 'Déclaration préalable', 'PC' => 'Permis de construire'][$d['procedure']] ?? 'À définir ensemble';
    $hello = $d['first_name'] ?: $d['name'];
@endphp
@section('content')
<section class="thanks-hero">
    <div class="wrap">
        <span class="thanks-mark">@include('site.partials.icon', ['name' => 'check'])</span>
        <span class="eyebrow">Demande envoyée</span>
        <h1>Merci {{ $hello }}, votre demande est bien reçue</h1>
        <p class="lead">Je vous rappelle sous 48 h ouvrées au <b class="nowrap">{{ $d['phone'] }}</b> pour parler de votre projet. Vous n'avez rien d'autre à faire d'ici là.</p>
    </div>
</section>

<section class="thanks-body">
    <div class="wrap thanks-grid">
        <div>
            <h2>La suite, étape par étape</h2>
            <ol class="next-steps">
                <li class="now">
                    <span class="n">1</span>
                    <div><b>Je vous appelle</b><p>Sous 48 h ouvrées, du lundi au vendredi. Nous parlons de votre projet et de votre terrain, sans engagement.</p></div>
                </li>
                <li>
                    <span class="n">2</span>
                    <div><b>Vous recevez votre devis <span class="nowrap">par e-mail</span></b><p>À l'adresse <span class="nowrap">{{ $d['email'] }}</span>. Vous le lisez tranquillement et vous le signez en ligne, en un clic.</p></div>
                </li>
                <li>
                    <span class="n">3</span>
                    <div><b>Je prépare votre dossier</b><p>Plans, formulaire CERFA et pièces pour la mairie. Vous suivez chaque étape dans votre espace client.</p></div>
                </li>
            </ol>

            @if ($guide && $guideTitle)
                <div class="thanks-guide">
                    <span class="eyebrow" style="margin-bottom:8px">En attendant mon appel</span>
                    <b>{{ $guideTitle }}</b>
                    <p>Les règles à connaître, les pièces du dossier et les questions qu'on me pose le plus souvent.</p>
                    <a class="btn blue sm" href="{{ route('site.project', $guide) }}">Lire le guide @include('site.partials.icon', ['name' => 'arrow'])</a>
                </div>
            @endif
            <a class="btn ghost" style="margin-top:24px" href="{{ route('home') }}">Revenir à l'accueil</a>
        </div>

        <aside>
            <div class="recap">
                <h2>Votre demande</h2>
                <div class="recap-project">
                    <span class="ico">@include('site.partials.icon', ['name' => $natureIcon, 'sw' => 1.4])</span>
                    <span><b>{{ $natureLabel }}</b><span class="muted">{{ $d['procedure'] === 'AUTRE' ? 'Démarche à définir ensemble' : $procedure }}</span></span>
                </div>
                <dl>
                    <div><dt>Commune</dt><dd>{{ $d['city'] }}</dd></div>
                    <div><dt>Nom</dt><dd>{{ $d['name'] }}</dd></div>
                    <div><dt>Téléphone</dt><dd class="nowrap">{{ $d['phone'] }}</dd></div>
                    <div><dt>E-mail</dt><dd>{{ $d['email'] }}</dd></div>
                    @if (filled($d['description']))<div class="full"><dt>Votre message</dt><dd>{{ \Illuminate\Support\Str::limit($d['description'], 280) }}</dd></div>@endif
                </dl>
            </div>
            <div class="recap-help">
                <b>Une erreur, une question ?</b>
                <p>Écrivez-moi ou appelez-moi directement, je corrige tout de suite.</p>
                <ul>
                    @if ($c->email)<li>@include('site.partials.icon', ['name' => 'mail'])<a href="mailto:{{ $c->email }}">{{ $c->email }}</a></li>@endif
                    @if ($c->phone)<li>@include('site.partials.icon', ['name' => 'phone'])<a class="nowrap" href="tel:{{ preg_replace('/\s+/', '', $c->phone) }}">{{ $c->phone }}</a></li>@endif
                </ul>
            </div>
        </aside>
    </div>
</section>
@endsection
