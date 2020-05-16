@extends('layouts.app')
 <!-- Algolia  -->
    <script src="https://cdn.jsdelivr.net/npm/places.js@1.18.2"></script>
@section('content')
  <div class="container-fluid">
    @include('layouts.steps')
    <div class="container">
        <form action="{{route('client.update', Auth::user())}}" class="needs-validation" method="POST" novalidate>
            @csrf
            {{-- ----------------------- Card 1 ---------------------- --}}
            <div class="card mt-5">
                <div class="card-header">
                    <h3 class="text-center">Le terrain</h3>
                </div>
                <div class="card-body">
                        <div class="form-row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="form-address">Adresse</label>
                                    <input type="search" value="" class="form-control @error('address') is-invalid @enderror" id="form-address"
                                        name="address" placeholder="Veuillez saisir votre adresse" />
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
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


{{-- ----------- Algolia Place Script --------------- --}}

    
<script>
  (function () {
    var placesAutocomplete = places({
        appId: '{{ env('ALGOLIA_APP_ID') }}',
        apiKey: '{{ env('ALGOLIA_SECRET') }}',
        container: document.querySelector('#form-address'),
        templates: {
            value: function (suggestion) {
            return suggestion.name;
        }
      }
    }).configure({
      type: 'address'
    });
    placesAutocomplete.on('change', function resultSelected(e) {
        let coordonnees = e.suggestion.latlng;

        document.querySelector('#departement').value = e.suggestion.county || '';
        document.querySelector('#form-city').value = e.suggestion.city || '';
        document.querySelector('#form-zip').value = e.suggestion.postcode || '';
    });
  })();
</script>

@endsection