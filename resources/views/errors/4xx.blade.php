@extends('errors.layout')
@section('code', $exception?->getStatusCode() ?? '')
@section('title', 'Cette page ne peut pas s’afficher')
@section('message', 'Revenez à l’accueil ou appelez-moi si le problème continue.')
@section('actions')
    <a class="btn" href="{{ url('/') }}">Retour à l’accueil</a>
@endsection
