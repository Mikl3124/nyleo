@extends('errors.layout')
@php($badSignature = ($exception ?? null) instanceof \Illuminate\Routing\Exceptions\InvalidSignatureException)
@section('code', '403')
@if ($badSignature && request()->routeIs('portal.magic'))
    {{-- Lien de connexion à l'espace client expiré ou abîmé --}}
    @section('title', 'Ce lien n’est plus valable')
    @if (auth('client')->check())
        @section('message', 'Ce lien est ancien, mais vous êtes toujours connecté : il suffit d’ouvrir votre espace.')
        @section('actions')
            <a class="btn cta" href="{{ url('/espace') }}">Ouvrir mon espace</a>
        @endsection
    @else
        @section('message')
            Les liens de connexion envoyés par e-mail ne servent que pendant 30 jours, et un lien coupé en deux dans l’e-mail ne fonctionne pas.
            Cliquez sur le bouton ci-dessous et indiquez votre adresse e-mail : vous recevrez un nouveau lien dans quelques minutes.
        @endsection
        @section('actions')
            <a class="btn cta" href="{{ url('/espace/connexion') }}">Recevoir un nouveau lien</a>
            <a class="btn secondary" href="{{ url('/') }}">Aller sur le site</a>
        @endsection
    @endif
@elseif ($badSignature && request()->routeIs('filament.admin.auth.password-reset.*'))
    {{-- Lien de réinitialisation du mot de passe de l'administration --}}
    @section('title', 'Ce lien n’est plus valable')
    @section('message', 'Le lien pour changer le mot de passe a expiré ou a été coupé dans l’e-mail. Demandez-en un nouveau.')
    @section('actions')
        <a class="btn" href="{{ url('/admin/password-reset/request') }}">Recevoir un nouveau lien</a>
    @endsection
@elseif ($badSignature)
    @section('title', 'Ce lien n’est plus valable')
    @section('message', 'Ce lien a expiré ou a été coupé dans l’e-mail.')
    @section('actions')
        <a class="btn" href="{{ url('/') }}">Retour à l’accueil</a>
    @endsection
@else
    @section('title', 'Accès refusé')
    @section('message', 'Vous n’avez pas accès à cette page.')
    @section('actions')
        <a class="btn" href="{{ url('/') }}">Retour à l’accueil</a>
    @endsection
@endif
