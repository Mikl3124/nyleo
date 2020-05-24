@extends('layouts.app')

@section('content')
  <div class="container-fluid">
    @include('layouts.steps')
    <div class="container">
        <form action="" class="needs-validation" method="POST" novalidate>
            @csrf
            {{-- ----------------------- Card 1 ---------------------- --}}
            <div class="card mt-5">
                <div class="card-header">
                    <h3 class="text-center">Quel est votre projet?</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="ProjetDescription">Courte descriptive des travaux envisagé</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" name="description" id="ProjetDescription" rows="3" ></textarea>
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
                                <input type="search" value="" class="form-control @error('address') is-invalid @enderror" id="form-address"
                                    name="address" placeholder="Veuillez saisir votre adresse" />
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="validationCustomCp">Code Postal</label>
                            <input type="text" class="form-control @error('cp') is-invalid @enderror" value="{{ old('cp') }}" name="cp" id="validationCustomCp"required>
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
                            <input type="text" class="form-control @error('town') is-invalid @enderror" value="{{old('town')}}" name="town" id="validationTown" required>
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
                            <label for="validationsCadastreSection">Section</label>
                            <input type="text" class="form-control @error('cadastre_section') is-invalid @enderror" value="{{old('cadastres_section')}}" name="cadastre-section" id="validationsCadastreSection" >
                            <div class="invalid-feedback">
                                Veuillez saisir la section
                            </div>
                            @error('cadastre_section')
                            <div>
                                <small  class="text-danger">{{ $message }}</small>
                            </div>
                            @enderror
                        </div>
                        <div class=" col-md-4 mb-3">
                            <label for="validationCadastreNumber">Numéro</label>
                            <input type="text" class="form-control @error('cadastre_number') is-invalid @enderror" value="{{old('cadastre_number')}}" name="cadastre_number" id="validationCadastreNumber" >
                            <div class="invalid-feedback">
                                Veuillez saisir votre ville
                            </div>
                            @error('cadastre_number')
                            <div>
                                <small  class="text-danger">{{ $message }}</small>
                            </div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="validationCadastreSuperficie">Superficie en m²</label>
                            <input type="text" class="form-control @error('cadastre_superficie') is-invalid @enderror" value="{{old('cadastre_superficie')}}" name="cadastre_superficie" id="validationCadastreSuperficie" required>
                            <div class="invalid-feedback">
                                Veuillez saisir votre ville
                            </div>
                            @error('cadastre_superficie')
                            <div>
                                <small  class="text-danger">{{ $message }}</small>
                            </div>
                            @enderror
                        </div>
                    </div>
                    <div class="form-check">
                      <input type="checkbox" name="multiple_parcelles" class="form-check-input" id="MultipleParcelles">
                      <label class="form-check-label" for="MultipleParcelles">D'autres parcelles sont concernées ?</label>
                    </div>
                </div>
            </div>

    <div class="container">
      @if (count($errors) > 0)
        <ul><li>{{ $error }}</li></ul>
      @endif
      <form action="{{ route('file.upload')}}" class="needs-validation" method="POST" enctype="multipart/form-data">
        @csrf
        <label class="label" for="file5">
        </label>
        <div class="file-uploader__message-area">
          <p>Select file</p>
        </div>
        <div class="file-chooser">
          <input type="file" class="file-chooser__input" id="file5" name="file5[]">
        </div>
        <button type="submit" class="btn btn-primary">Envoyer</button>
      </form>

    </div>


@endsection

