@extends('layouts.app')
@section('content')
    <div class="container-fluid">
      <div class="container">
        <div class="jumbotron">
            <h1 class="display-4 text-center">Bonjour et Bienvenue</h1>
            <p class="lead mt-4 text-center">Cette plateforme de communication a été conçue pour faciliter, et fluidifier nos différents échanges.</p>
            <p class="lead mt-4 text-center">Pour commencer, je vous invite à créer un mot de passe personnalisé:</p>
            <hr class="my-4">

        <div class="card mt-3">
            <div class="card-body">
                <form action="{{ route('change.password', Auth::user()) }} " method="post" id="identicalForm" >
                    @csrf
                    <div class="text-center mb-3">
                        <h3>CHANGEMENT DE MOT DE PASSE</h3>
                    </div>
                    <div class="form-row">
                      <div class="col-md-6 col-sm-12 mb-4">
                          <div class="input-group" id="show_hide_password_1">
                              <input id="password" placeholder="Mot de passe" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                              <div class="input-group-append">
                                  <span class="input-group-text"><a href=""><i class="fa fa-eye-slash" aria-hidden="true"></i></a></span>
                              </div>
                              @error('password')
                                  <span class="invalid-feedback text-danger" role="alert">
                                      <strong>{{ $message }}</strong>
                                  </span>
                              @enderror
                          </div>
                      </div>
                      <div class="col-md-6 col-sm-12 mb-4">
                          <div class="input-group" id="show_hide_password_2">
                              <input id="password-confirm" placeholder="Confirmer le mot de passe" type="password" class="form-control @error('password') is-invalid @enderror" name="password_confirmation" required autocomplete="new-password">
                              <div class="input-group-append">
                                  <span class="input-group-text"><a href=""><i class="fa fa-eye-slash" aria-hidden="true"></i></a></span>
                              </div>
                              @error('password')
                                  <span class="invalid-feedback text-danger" role="alert">
                                      <strong>{{ $message }}</strong>
                                  </span>
                              @enderror
                          </div>
                      </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-success mt-3"></i>Modifier</button>
                    </div>

                </form>
            </div>
        </div>
          </div>
      </div>
    </div>

<show-password></show-password>
@endsection



