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
                    <h3 class="text-center">Quel est votre projet?</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="ProjetDescription">Courte descriptive des travaux envisagé</label>
                        <textarea class="form-control" id="ProjetDescription" rows="3"></textarea>
                    </div>
                </div>
            </div>
            {{-- ----------------------- Card 2 ---------------------- --}}
            <div class="card mt-5">
                <div class="card-header">
                    <h3 class="text-center">Où se situe le projet?</h3>
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
                    </div>
                </div>
            </div>
                {{-- ----------------------- Card 3 ---------------------- --}}
            <div class="card mt-5">
                <div class="card-header">
                    <h3 class="text-center">Connaissez-vous les références cadastrales?</h3>
                </div>


                <div class="card-body">
                    <div class="form-row">
                        <div class="col-md-4 mb-3">
                            <label for="validationCustomCp">Section</label>
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
                        <div class="col-md-4 mb-3">
                            <label for="validationTown">Numéro</label>
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
                        <div class="col-md-4 mb-3">
                            <label for="validationTown">Superficie en m²</label>
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
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="multipleParcellesCadastre">
                        <label class="form-check-label" for="multipleParcellesCadastre">Plusieurs parcelles sont concernées par le projet</label>
                    </div>
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
