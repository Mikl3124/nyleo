@extends('layouts.app')
@section('content')
  <div class="container-fluid row">
    <div class="col-sm-12 col-md-2">
      @include('layouts.steps')
    </div>
    <div class="col-sm-12 col-md-8">
      <div class="container">
        <div class="card mt-5 ">
          <h4 class="card-header text-center">Votre projet</h4>
          <div class="card-body">
            <div class="row">
                <div class="col-sm-12 col-md-6">
                    <h5 class=text-center>Détails</h5>
                    <p class="card-text">{{ $projet->description }}</p>
                    <p class="card-text">Ligne 2</p>
                </div>
                <div class="col-sm-12 col-md-6">
                    <h5 class=text-center>Localisation</h5>
                    <p class="card-text">{{ $projet->address }}</p>
                    <p class="card-text">{{ $projet->cp }} {{ $projet->town }}</p>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12 col-md-6">
                    <p class="card-text">{{ $projet->description }}</p>
                    <p class="card-text">Ligne 2</p>
                </div>
                <div class="col-sm-12 col-md-6">
                    <p class="card-text">{{ $projet->address }}</p>
                    <p class="card-text">{{ $projet->cp }} {{ $projet->town }}</p>
                </div>
            </div>
            
            <div class="text-center mt-5">
              <a class="btn btn-secondary" href="{{ route('home')}}">Annuler</a>
              <a class="btn btn-primary" href="{{ route('projet.edit', $projet)}}">Modifier</a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-12 col-md-2">
        @include('layouts.vertical-navbar')
    </div>
  </div>

@endsection
