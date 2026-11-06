@extends('layouts.site')
@section('title', 'Mentions légales')
@php
    $c = \App\Models\CompanySetting::current();
@endphp
@section('content')
@include('site.partials.page-hero', ['crumb' => 'Mentions légales', 'kicker' => 'Informations', 'h1' => 'Mentions légales', 'lead' => 'Informations sur l\'éditeur et l\'hébergeur du site.'])
<section>
    <div class="wrap prose">
        <h2>Éditeur du site</h2>
        <p>{{ $c->company_name ?: $c->displayName() }}@if ($c->legal_form), {{ $c->legal_form }}@endif<br>
            @if ($c->address){{ $c->address }}, {{ $c->postal_code }} {{ $c->city }}<br>@endif
            @if ($c->siret)SIRET : {{ $c->siret }}<br>@endif
            @if ($c->rcs){{ $c->rcs }}<br>@endif
            @if ($c->phone)Téléphone : {{ $c->phone }}<br>@endif
            @if ($c->email)E-mail : {{ $c->email }}@endif</p>
        <p>Directeur de la publication : Mickaël Delpech.</p>
        <h2>Hébergement</h2>
        <p>{{ config('nyleo.host_name') }}<br>{{ config('nyleo.host_address') }}</p>
        <h2>Propriété intellectuelle</h2>
        <p>Les textes, dessins et éléments graphiques de ce site sont la propriété de l'éditeur. Toute reproduction sans autorisation est interdite.</p>
        <h2>Données personnelles et cookies</h2>
        <p>Voir la page <a href="{{ route('privacy') }}">Confidentialité</a>.</p>
    </div>
</section>
@endsection
