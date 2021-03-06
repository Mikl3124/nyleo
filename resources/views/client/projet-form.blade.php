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
      <form action="{{ route('projet.store') }}" class="needs-validation" method="POST" novalidate>
          @csrf
          {{-- ----------------------- Card 1 ---------------------- --}}
          <div class="card mt-5">
              <div class="card-header">
                  <h3 class="text-center">Quel est votre projet?</h3>
              </div>
              <div class="card-body">
                  <div class="form-group">
                      <label for="ProjetDescription">Description des travaux envisagés</label>
                      <textarea class="form-control @error('description') is-invalid @enderror" name="description" id="ProjetDescription" rows="3" >{{ old('description') }}</textarea>
                      @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
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
                          <input id="form-address" type="search" value="{{ old('address') }}" class="form-control @error('address') is-invalid @enderror" id="form-address"
                                  name="address" placeholder="Veuillez saisir votre adresse" />
                              @error('address')
                                  <div class="invalid-feedback">{{ $message }}</div>
                              @enderror
                          </div>
                      </div>
                      <div class="col-md-6 mb-3">
                          <label for="validationCustomCp">Code Postal</label>
                          <input id="form-zip" type="text" class="form-control @error('cp') is-invalid @enderror" value="{{ old('cp') }}" name="cp" id="validationCustomCp"required>
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
                          <input id="form-city" type="text" class="form-control @error('town') is-invalid @enderror" value="{{ old('town') }}" name="town" id="validationTown" required>
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
                          <label for="validationsSection">Section</label>
                          <input type="text" class="form-control @error('section') is-invalid @enderror" value="{{ old('section') }}" name="section" id="validationsSection" >
                          <div class="invalid-feedback">
                              Veuillez saisir la section
                          </div>
                          @error('section')
                          <div>
                              <small  class="text-danger">{{ $message }}</small>
                          </div>
                          @enderror
                      </div>
                      <div class=" col-md-4 mb-3">
                          <label for="validationNumber">Numéro</label>
                          <input type="text" class="form-control @error('number') is-invalid @enderror" value="{{ old('number') }}" name="number" id="validationNumber" >
                          <div class="invalid-feedback">
                              Veuillez saisir votre ville
                          </div>
                          @error('number')
                          <div>
                              <small  class="text-danger">{{ $message }}</small>
                          </div>
                          @enderror
                      </div>
                      <div class="col-md-4 mb-3">
                          <label for="validationSuperficie">Superficie en m²</label>
                          <input type="text" class="form-control @error('superficie') is-invalid @enderror" value="{{ old('superficie') }}" name="superficie" id="validationSuperficie">
                          <div class="invalid-feedback">
                              Veuillez saisir la superficie du terrain
                          </div>
                          @error('superficie')
                          <div>
                              <small  class="text-danger">{{ $message }}</small>
                          </div>
                          @enderror
                      </div>
                  </div>
                  <div class="form-check">
                    <input type="checkbox" name="multiple_parcelles" value={{ old('multiple_parcelles') }}  class="form-check-input" id="MultipleParcelles">
                    <label class="form-check-label" for="MultipleParcelles">D'autres parcelles sont concernées ?</label>
                  </div>
              </div>
          </div>

          <button class="btn btn-primary mt-3" type="submit">Valider</button>
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

