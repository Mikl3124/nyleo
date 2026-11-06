@extends('errors.layout')
@section('code', '429')
@section('title', 'Trop d’essais d’affilée')
@section('message', 'Par sécurité, merci de patienter une dizaine de minutes avant de réessayer.')
@section('actions')
    <a class="btn" href="{{ url('/') }}">Retour à l’accueil</a>
@endsection
