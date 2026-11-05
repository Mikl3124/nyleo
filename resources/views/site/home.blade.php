@extends('layouts.portal')
@section('title', 'Plans de permis de construire et déclaration préalable en Dordogne et Gironde')
@section('hero')
<section class="hero">
    <div class="wrap">
        <h1>Vos plans pour la mairie, sans prise de tête</h1>
        <p>Déclaration préalable, permis de construire : je réalise les plans, je remplis le CERFA et je dépose le dossier en mairie pour vous, en Dordogne et en Gironde.</p>
        <p><a class="btn" style="background:#fff;color:#0f766e" href="#contact">Demander un devis gratuit</a>
           <a class="btn secondary" style="background:transparent;color:#fff;border-color:#fff;margin-left:8px" href="{{ route('portal.login') }}">Espace client</a></p>
    </div>
</section>
@endsection
@section('content')
<div class="features">
    <div class="card"><h3>Déclaration préalable</h3><p class="muted">Véranda, pergola, carport, abri, piscine, clôture, ouvertures : plans, insertion et dépôt.</p></div>
    <div class="card"><h3>Permis de construire</h3><p class="muted">Maison individuelle, extension, garage. Au-delà de 150 m², je travaille avec un architecte partenaire.</p></div>
    <div class="card"><h3>Suivi en ligne</h3><p class="muted">Vous signez le devis, validez les plans et suivez l'instruction depuis votre espace, en quelques clics.</p></div>
</div>

<div class="card">
    <h2>Comment ça se passe ?</h2>
    <ol class="steps">
        <li class="done"><span class="dot">1</span><span>On échange par téléphone sur votre projet, je vous envoie un devis.</span></li>
        <li class="done"><span class="dot">2</span><span>Vous signez en ligne et m'envoyez quelques photos.</span></li>
        <li class="done"><span class="dot">3</span><span>Je réalise les plans, vous les validez.</span></li>
        <li class="done"><span class="dot">4</span><span>Je dépose le dossier en mairie et je suis l'instruction jusqu'à la décision.</span></li>
    </ol>
</div>

<div class="card" id="contact">
    <h2>Demander un devis</h2>
    <form method="post" action="{{ route('site.request') }}">
        @csrf
        <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">
        <div class="grid2">
            <div><label for="last_name">Nom</label><input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" required></div>
            <div><label for="first_name">Prénom</label><input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}"></div>
            <div><label for="email">E-mail</label><input id="email" type="email" name="email" value="{{ old('email') }}" required></div>
            <div><label for="phone">Téléphone</label><input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required></div>
            <div><label for="procedure">Type de demande</label>
                <select id="procedure" name="procedure" required>
                    <option value="DP">Déclaration préalable</option>
                    <option value="PC">Permis de construire</option>
                    <option value="AUTRE">Je ne sais pas</option>
                </select></div>
            <div><label for="nature">Projet</label>
                <select id="nature" name="nature">
                    @foreach (\App\Filament\Resources\Dossiers\DossierResource::NATURES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select></div>
            <div><label for="site_city">Commune du projet</label><input id="site_city" type="text" name="site_city" value="{{ old('site_city') }}" required></div>
            <div><label for="site_postal_code">Code postal</label><input id="site_postal_code" type="text" name="site_postal_code" value="{{ old('site_postal_code') }}"></div>
        </div>
        <label for="description">Décrivez votre projet en quelques mots</label>
        <textarea id="description" name="description" rows="4">{{ old('description') }}</textarea>
        <p><button class="btn" type="submit">Envoyer ma demande</button></p>
    </form>
</div>
@endsection
