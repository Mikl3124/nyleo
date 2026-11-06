@extends('errors.layout')
@section('code', '404')
@section('title', 'Cette page n’existe pas')
@section('message', 'L’adresse a peut-être été mal recopiée, ou la page a changé de place.')
@section('actions')
    <a class="btn" href="{{ url('/') }}">Retour à l’accueil</a>
    <a class="btn secondary" href="{{ url('/contact') }}">Demander un devis gratuit</a>
@endsection
