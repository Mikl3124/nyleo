@extends('layouts.app')
@section('content')
  <div class="container-fluid">
    @include('layouts.steps')
        <div class="container">
            <h1 class="text-center mt-3">Vos informations</h1>
            <p class="font-weight-bold">Votre prenom: <span class="font-weight-normal">{{ Auth::user()-> firstname }}</span></p>
            <p>Votre nom: {{ Auth::user()-> lastname }}</p>
            <a class="btn btn-primary" href="{{ route('client.edit')}}">Modifier</a>
        </div>
@endsection