@extends('layouts.portal')
@section('title', 'Mentions légales')
@section('content')
@php($c = \App\Models\CompanySetting::current())
<div class="card">
    <h1>Mentions légales</h1>
    <h2>Éditeur du site</h2>
    <p>{{ $c->company_name ?: $c->displayName() }}@if ($c->legal_form) — {{ $c->legal_form }}@endif<br>
        @if ($c->address){{ $c->address }}, {{ $c->postal_code }} {{ $c->city }}<br>@endif
        @if ($c->siret)SIRET : {{ $c->siret }}<br>@endif
        @if ($c->rcs){{ $c->rcs }}<br>@endif
        @if ($c->phone)Téléphone : {{ $c->phone }}<br>@endif
        @if ($c->email)E-mail : {{ $c->email }}@endif</p>
    <p>Directeur de la publication : {{ $c->company_name ?: 'Mickaël Delpech' }}.</p>
    <h2>Hébergement</h2>
    <p>{{ config('nyleo.host_name') }}<br>{{ config('nyleo.host_address') }}</p>
    <h2>Données personnelles</h2>
    <p>Les informations recueillies (identité, coordonnées, informations sur votre projet, date et lieu de naissance pour le formulaire CERFA) sont utilisées uniquement pour établir vos devis, factures et dossiers d'urbanisme, et pour vous tenir informé de leur avancement. Elles ne sont jamais revendues.</p>
    <p>Les factures sont conservées 10 ans (obligation comptable), les autres données 3 ans après la fin de la relation. Vous pouvez demander l'accès, la rectification ou la suppression de vos données en écrivant à {{ $c->email ?: "l'adresse de contact" }}. Vous pouvez saisir la CNIL (cnil.fr).</p>
    <h2>Cookies</h2>
    <p>Ce site n'utilise que des cookies techniques nécessaires à la connexion à votre espace. Aucun cookie publicitaire ni de mesure d'audience.</p>
</div>
@endsection
