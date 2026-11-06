@extends('errors.layout')
@section('code', $exception?->getStatusCode() ?? '')
@section('title', 'Un problème est survenu')
@section('message', 'Ce n’est pas de votre faute : réessayez dans quelques minutes ou appelez-moi.')
@section('actions')
    <a class="btn" href="{{ url('/') }}">Retour à l’accueil</a>
@endsection
