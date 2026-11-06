@extends('errors.layout')
@section('code', '419')
@section('title', 'Cette page a expiré')
@section('message', 'Par sécurité, une page restée ouverte trop longtemps doit être rechargée. Rien n’a été envoyé : revenez en arrière, rechargez la page et recommencez.')
@section('actions')
    <a class="btn" href="{{ url()->previous(url('/')) }}">Revenir en arrière</a>
@endsection
