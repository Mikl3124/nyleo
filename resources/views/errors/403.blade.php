@extends('errors.layout')
@php($expiredLink = ($exception ?? null) instanceof \Illuminate\Routing\Exceptions\InvalidSignatureException)
@section('code', '403')
@if ($expiredLink)
    @section('title', 'Ce lien n’est plus valable')
    @section('message')
        Les liens de connexion envoyés par e-mail ne servent que pendant 30 jours. Un lien coupé en deux dans l’e-mail ne fonctionne pas non plus.
        Indiquez votre adresse e-mail : vous recevrez tout de suite un nouveau lien.
    @endsection
    @section('actions')
        @if (auth('client')->check())
            <a class="btn cta" href="{{ url('/espace') }}">Ouvrir mon espace</a>
        @else
            <a class="btn cta" href="{{ url('/espace/connexion') }}">Recevoir un nouveau lien</a>
        @endif
        <a class="btn secondary" href="{{ url('/') }}">Aller sur le site</a>
    @endsection
@else
    @section('title', 'Accès refusé')
    @section('message', 'Vous n’avez pas accès à cette page. Si vous pensez que c’est une erreur, appelez-moi.')
    @section('actions')
        <a class="btn" href="{{ url('/') }}">Retour à l’accueil</a>
    @endsection
@endif
