@extends('layouts.app')
@section('content')
  <div class="container-fluid row">
    <div class="col-sm-12 col-md-2">
      @include('layouts.steps')
    </div>
    <div class="col-sm-12 col-md-8">
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
              <p><i class="fas fa-arrow-right"></i>  Prochaine étape: <a href="{{ route('projet.create', Auth::user()) }}"> Remplir la fiche projet</a></p>
          @break

          @case(2)
              <p><a class="step-complete" href="{{ route('client.show', Auth::user()) }}"> <i class="fas fa-check"></i> Fiche client</a></p>
              <p><a class="step-complete" href="{{ route('projet.show', Auth::user()) }}"> <i class="fas fa-check"></i> Fiche projet</a></p>
              @if($projet->as_quote !== 0)
                <p><i class="fas fa-arrow-right"></i> Prochaine étape: <a href="{{ route('quote.show', Auth::user()) }}">Valider votre devis</a></p>
              @else
                <p><i class="fas fa-arrow-right"></i> <em> Votre devis n'a pas encore été saisi, vous serez notifié par email, dès qu'il sera disponible </em></p>
              @endif
          @break

          @case(3)
              <p><a class="step-complete" href="{{ route('client.show', Auth::user()) }}"> <i class="fas fa-check"></i> Fiche client</a></p>
              <p><a class="step-complete" href="{{ route('projet.show', Auth::user()) }}"> <i class="fas fa-check"></i> Fiche projet</a></p>
              <p><a class="step-complete" href="{{ route('quote.show', Auth::user()) }} "> <i class="fas fa-check"></i> Votre Devis</a></p>
              @if($projet->as_avantProjet !== 0)
                <p><i class="fas fa-arrow-right"></i> Prochaine étape: <a href="{{ route('avantprojet.show', Auth::user()) }}">Valider votre avant-projet</a></p>
              @else
                <p><i class="fas fa-arrow-right"></i> <em>L'avant projet n'est pas encore disponible, vous serez notifié par email, dès qu'il sera disponible</em></p>
              @endif
          @break

          @default
              Default case...
      @endswitch
      </div>
      <div class="col-sm-12 col-md-2">
        @include('layouts.vertical-navbar')
      </div>
  </div>

@endsection



