@extends('layouts.app')
@section('content')
  <div class="container-fluid row">
    <div class="col-sm-12 col-md-2">
      @include('layouts.steps')
    </div>
    <div class="col-sm-12 col-md-10">
      @if(Auth::user()->firstname)
          <h1 class="text-center">
              Bienvenue {{Auth::user()->firstname}}
          </h1>
      @else
          <h1 class="text-center">
              Bienvenue sur la plateforme Nyleo
          </h1>
      @endif
      @switch($step)
          @case(0)
              <p>Prochaine étape: <a href="{{ route('client.edit', Auth::user()) }}">Remplir votre fiche client</a></p>
          @break

          @case(1)
              <p><a class="step-complete" href="{{ route('client.show', Auth::user()) }}"> <i class="fas fa-check"></i> Fiche client</a></p>
              <p><i class="fas fa-arrow-right"></i>  Prochaine étape: <a href="{{ route('projet.edit', Auth::user()) }}"> Remplir la fiche projet</a></p>
          @break

          @case(2)
              <p><a class="step-complete" href="{{ route('client.show', Auth::user()) }}"> <i class="fas fa-check"></i> Fiche client</a></p>
              <p><a class="step-complete" href="{{ route('client.show', Auth::user()) }}"> <i class="fas fa-check"></i> Fiche projet</a></p>
              <p><i class="fas fa-arrow-right"></i> Prochaine étape: <a href="{{ route('client.edit', Auth::user()) }}">Valider votre devis</a></p>
          @break

          @default
              Default case...
      @endswitch
        </div>

@endsection



