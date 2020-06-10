@extends('layouts.app')
@section('extra-script')
  <script src="https://cdn.jsdelivr.net/npm/places.js@1.19.0"></script>
@endsection
@section('content')

  <div class="container-fluid row">
    <div class="col-sm-12 col-md-2">
      @include('layouts.steps')
    </div>
    <div class="col-sm-12 col-md-10">
        <form action="{{route('client.update', Auth::user())}}" class="needs-validation" method="POST" novalidate>
          @csrf
          {{-- ----------------------- Card 1 ---------------------- --}}
          <div class="card">
            <div class="card-header">
              <h3 class="text-center">Identité</h3>
            </div>
            <div class="card-body">
              <div class="form-row">
                <div class="col-md-6 mb-3">
                  <label for="validationCustomFirstname">Prénom</label>
                  <input type="text" class="form-control @error('firstname') is-invalid @enderror" value="{{old('firstname', Auth::user()->firstname)}}" name="firstname" id="validationCustomFirstname"required>
                  <div class="invalid-feedback">
                    Veuillez saisir votre prénom
                  </div>
                  @error('firstname')
                      <div>
                          <small  class="text-danger">{{ $message }}</small>
                      </div>
                  @enderror
                </div>
                <div class="col-md-6 mb-3">
                  <label for="validationCustomName">Nom</label>
                  <input type="text" class="form-control @error('lastname') is-invalid @enderror" value="{{old('lastname', Auth::user()->lastname)}}" name="lastname" id="validationCustomName"required>
                  <div class="invalid-feedback">
                    Veuillez saisir votre nom
                  </div>
                  @error('lastname')
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
                    Veuillez indiquer votre date de naissance
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
                    Veuillez saisir votre lieu de naissance
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
                    <input type="search" id="form-address" class="form-control @error('address') is-invalid @enderror" value="{{old('address', Auth::user()->address)}}" name="address" id="validationCustomAdress" required>
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
                    <label for="validationCustomCp">Code Postal</label>
                    <input id="form-zip" type="text" class="form-control @error('cp') is-invalid @enderror" value="{{old('cp', Auth::user()->cp)}}" name="cp" id="validationCustomCp"required>
                    <div class="invalid-feedback">
                      Veuillez saisir le code postal
                    </div>
                    @error('cp')
                      <div>
                          <small  class="text-danger">{{ $message }}</small>
                      </div>
                    @enderror
                  </div>
                  <div class="col-md-3 mb-3">
                    <label for="validationTown">Ville</label>
                    <input id="form-city" type="text" class="form-control @error('town') is-invalid @enderror" value="{{old('town', Auth::user()->town)}}" name="town" id="validationTown" required>
                    <div class="invalid-feedback">
                      Veuillez saisir votre ville
                    </div>
                    @error('town')
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
            <div class="text-center">
              <a class="btn btn-secondary mt-3" href="{{ route('home')}}">Annuler</a>
              <button class="btn btn-primary mt-3" type="submit">Valider</button>
            </div>

          </div>
        </div>
      </form>
    </div>

  @section('extra-js')
    <script type="application/javascript">
      window.onload = function(){

      var placesAutocomplete = places({
        appId: '{{ env('ALGOLIA_APP_ID') }}',
        apiKey: '{{ env('ALGOLIA_SECRET') }}',
        container: document.querySelector('#form-address'),
        templates: {
          value: function(suggestion) {
            return suggestion.name;
          }
        }
      }).configure({
        type: 'address'
      });
      placesAutocomplete.on('change', function resultSelected(e) {
        document.querySelector('#form-city').value = e.suggestion.city || '';
        document.querySelector('#form-zip').value = e.suggestion.postcode || '';
      });
      };
    </script>
  @endsection

@endsection



