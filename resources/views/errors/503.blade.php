@extends('errors.layout')
@section('code', '503')
@section('title', 'Le site revient dans quelques minutes')
@section('message', 'Une mise à jour est en cours. Merci de réessayer un peu plus tard.')
@section('actions')
    <a class="btn" href="">Réessayer</a>
@endsection
