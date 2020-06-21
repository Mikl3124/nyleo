@extends('layouts.app')
@section('content')
  <div class="container">
    <div class="card" >
      <div class="card-header">
        <h4>Avant projet de {{ $user->firstname}} {{ $user->lastname}}</h4>
      </div>
      <div class="card-body">
          <form action="{{ route('admin.avantProjet.store', $user)}}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="selectProjet">Selectionner le projet</label>
                <select class="form-control" name="projetId">
                  @foreach ($projets as $projet)
                      <option value="{{ $projet->id }}">{{ $projet->description }}</option>
                  @endforeach
                </select>
              </div>
            <div class="row">  

              <input name="userId" type="hidden" value="{{ $user->id }}">
              <div class="col-md-6 mb-3">
                <div class="form-group">
                    <input type="file" name="avantProjetFile" class="form-control-file @error('avantProjetFile') is-invalid @enderror" id="avantProjetFile" value="{{ old('avantProjetFile') }}">
                    @error('avantProjetFile')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
              </div>
            </div>
              <button class="btn btn-primary" type="submit">Envoyer</button>
          </form>
        </div>
    </div>
  </div>

@endsection
