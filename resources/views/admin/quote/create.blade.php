@extends('layouts.app')
@section('content')
  <div class="container">
    <div class="card" >
      <div class="card-header">
        <h4>Saisie du devis pour {{ $user->firstname}} {{ $user->lastname}}</h4>
      </div>
      <div class="card-body">
          <form action="{{ route('admin.quote.store', $user)}}" method="post" enctype="multipart/form-data">
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
              <div class="col-md-6 mb-3">
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">€</span>
                  </div>
                  <input type="text" name="amount" class="form-control" id="validationServerUsername" placeholder="Montant du devis" required>
                </div>
              </div>
              <input name="userId" type="hidden" value="{{ $user->id }}">
              <div class="col-md-6 mb-3">
                <div class="form-group">
                    <input type="file" name="quoteFile" class="form-control-file @error('file_projet') is-invalid @enderror" id="file-message" value="{{ old('file-message') }}" name="file_message">
                    @error('file_message')
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
