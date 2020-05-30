@extends('layouts.app')
@section('content')
  <div class="container-fluid row">
    <div class="col-sm-12 col-md-2">
      @include('layouts.steps')
    </div>
    <div class="col-sm-12 col-md-10">
      <div class="container">
        <div class="card mt-5 ">
          <h4 class="card-header text-center">Vos informations</h4>
          <div class="card-body">
            <div class="row">
              <div class="col-sm-12 col-md-6">
                <p class="card-text">{{ Auth::user()->firstname }} {{ Auth::user()->lastname }}</p>
                <p class="card-text">Né le {{ Carbon\Carbon::parse(Auth::user()->birth)->isoFormat('Do MMMM YYYY') }} à {{ Auth::user()->birthplace}}</p>
              </div>
              <div class="col-sm-12 col-md-6">
                <p class="card-text">{{ Auth::user()->address}}</p>
                <p class="card-text">{{ Auth::user()->cp }} {{ Auth::user()->town }}</p>
              </div>
            </div>
            <div class="text-center mt-5">
              <a class="btn btn-secondary" href="{{ route('home')}}">Annuler</a>
              <a class="btn btn-primary" href="{{ route('client.edit')}}">Modifier</a>
            </div>
          </div>
        </div>
      </div>
    </div>  

@endsection
