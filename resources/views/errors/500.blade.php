@extends('errors.layout')
@section('code', '500')
@section('title', 'Un problème est survenu')
@section('message', 'Ce n’est pas de votre faute : une erreur s’est produite de mon côté. Réessayez dans quelques minutes.')
@section('actions')
    <a class="btn" href="{{ url('/') }}">Retour à l’accueil</a>
@endsection
