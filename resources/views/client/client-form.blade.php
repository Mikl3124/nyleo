@extends('layouts.app')
@section('content')
  <div class="container-fluid">
    @include('layouts.steps')
      <div class="container">
        <form action="{{route('client.update', Auth::user())}}" class="needs-validation" method="POST" novalidate>
          @csrf
          {{-- ----------------------- Card 1 ---------------------- --}}
          <div class="card mt-5">
            <div class="card-header">
              <h3 class="text-center">Identité</h3>
            </div>
            <div class="card-body">
              <div class="form-row">
                <div class="col-md-6 mb-3">
                  <label for="validationCustomName">Nom</label>
                  <input type="text" class="form-control @error('lastname') is-invalid @enderror" value="{{old('lastname', Auth::user()->firstname)}}" name="lastname" id="validationCustomName"required>
                  <div class="invalid-feedback">
                    Veullez saisir votre nom
                  </div>
                  @error('lastname')
                      <div>
                          <small  class="text-danger">{{ $message }}</small>
                      </div>
                  @enderror
                </div>
                <div class="col-md-6 mb-3">
                  <label for="validationCustomFirstname">Prénom</label>
                  <input type="text" class="form-control @error('firstname') is-invalid @enderror" value="{{old('firstname', Auth::user()->firstname)}}" name="firstname" id="validationCustomFirstname"required>
                  <div class="invalid-feedback">
                    Veullez saisir votre prénom
                  </div>
                  @error('firstname')
                      <div>
                          <small  class="text-danger">{{ $message }}</small>
                      </div>
                  @enderror                
                </div>      
              </div>
              <div class="form-row">
                <div class="col-md-6 mb-3">
                  <label for="validationCustomNaissance">Date de naissance</label>
                  <input type="date" class="form-control @error('birth') is-invalid @enderror" value="{{old('birth', Auth::user()->birth)}}" name="birth" id="validationCustomNaissance"required>
                  <div class="invalid-feedback">
                    Veullez indiquer votre date de naissance
                  </div>
                  @error('birth')
                      <div>
                          <small  class="text-danger">{{ $message }}</small>
                      </div>
                  @enderror
                </div>
                <div class="col-md-6 mb-3">
                  <label for="validationCustomNaissanceLieu">Lieu de Naissance</label>
                  <input type="text" class="form-control @error('birthplace') is-invalid @enderror" value="{{old('birthplace', Auth::user()->birthplace)}}" name= "birthplace" id="validationCustomNaissanceLieu"required>
                  <div class="invalid-feedback">
                    Veullez saisir votre lieu de naissance
                  </div>
                  @error('lastname')
                      <div>
                          <small  class="birthplace">{{ $message }}</small>
                      </div>
                  @enderror
                </div> 
              </div>
            </div>
          </div>
            {{-- ----------------------- Card 2 ---------------------- --}}
          <div class="card mt-3">
            <div class="card-header">
              <h3 class="text-center">Vos Coordonnées</h3>
            </div>
              <div class="card-body">
                <div class="form-row">
                  <div class="col-md-6 mb-3">
                    <label for="validationCustomAdress">Adresse</label>
                    <input type="text" class="form-control @error('address') is-invalid @enderror" value="{{old('address', Auth::user()->address)}}" name="address" id="validationCustomAdress" required>
                    <div class="invalid-feedback">
                      Veuillez saisir votre adresse
                    </div>
                    @error('address')
                      <div>
                          <small  class="text-danger">{{ $message }}</small>
                      </div>
                    @enderror
                  </div>
                  <div class="col-md-3 mb-3">
                    <label for="validationTown">Ville</label>
                    <input type="text" class="form-control @error('town') is-invalid @enderror" value="{{old('town', Auth::user()->town)}}" name="town" id="validationTown" required>
                    <div class="invalid-feedback">
                      Veuillez saisir votre ville
                    </div>
                    @error('town')
                      <div>
                          <small  class="text-danger">{{ $message }}</small>
                      </div>
                    @enderror
                  </div>
                  <div class="col-md-3 mb-3">
                    <label for="validationCustomCp">Code Postal</label>
                    <input type="text" class="form-control @error('cp') is-invalid @enderror" value="{{old('cp', Auth::user()->cp)}}" name="cp" id="validationCustomCp"required>
                    <div class="invalid-feedback">
                      Veuillez saisir le code postal
                    </div>
                    @error('cp')
                      <div>
                          <small  class="text-danger">{{ $message }}</small>
                      </div>
                    @enderror
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="validationCustomEmail">Email</label>
                    <div class="input-group">
                      <input type="email" class="form-control @error('email') is-invalid @enderror" value="{{old('email', Auth::user()->email)}}" name="email" id="validationCustomEmail" value="{{ Auth::user()->email }}" required>
                      <div class="invalid-feedback">
                        Veuillez saisir votre email
                      </div>
                      @error('email')
                      <div>
                          <small  class="text-danger">{{ $message }}</small>
                      </div>
                    @enderror
                    </div>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="validationCustomPhone">Téléphone</label>
                    <div class="input-group">
                      <input type="text" class="form-control @error('phone') is-invalid @enderror" value="{{old('phone', Auth::user()->phone)}}" name="phone" id="validationCustomPhone" required>
                      <div class="invalid-feedback">
                        Veuillez saisir votre numéro de téléphone
                      </div>
                      @error('phone')
                      <div>
                          <small  class="text-danger">{{ $message }}</small>
                      </div>
                    @enderror
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <button class="btn btn-primary mt-3" type="submit">Valider</button>     
          </div>      
        </div>
      </form>
          {{-- ------------------ Recaptitualtif ---------------------- --}}
          
      </div>
    </div>

    <validation-form></validation-form>


@endsection


