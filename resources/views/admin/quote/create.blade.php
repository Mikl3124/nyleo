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
                  <input type="number" name="amount" class="form-control" placeholder="Montant du devis" required>
                </div>
              </div>
              <input name="userId" type="hidden" value="{{ $user->id }}">
              <div class="col-md-6 mb-3">
                <div class="form-group">
                    <input type="file" name="quoteFile" class="form-control-file @error('file_projet') is-invalid @enderror" id="file-message" value="{{ old('file-message') }}" name="file_message" required>
                    @error('file_message')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
              </div>
            </div>
            <div class="form-row mb-3">
                <div class="col-md-2 col-sm-12">
                  <div class="input-group ">
                    <div class="input-group-prepend">
                      <span class="input-group-text">€</span>
                    </div>
                    <input type="number" class="form-control" placeholder="Prix de l'option 1" name="option1[]">
                  </div>
                </div>
                <div class="col-md-10">
                  <input type="text" class="form-control" placeholder="Description" name="option1[]">
                </div>
            </div>
            <div class="form-row mb-3">
                <div class="col-md-2 col-sm-12">
                  <div class="input-group ">
                    <div class="input-group-prepend">
                      <span class="input-group-text">€</span>
                    </div>
                    <input type="number" class="form-control" placeholder="Prix de l'option 2" name="option2[]">
                  </div>
                </div>
                <div class="col-md-10">
                  <input type="text" class="form-control" placeholder="Description" name="option2[]">
                </div>
            </div>
            <div class="form-row mb-3">
                <div class="col-md-2 col-sm-12">
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">€</span>
                    </div>
                    <input type="number" class="form-control" placeholder="Prix de l'option 3" name="option3[]">
                  </div>
                </div>
                <div class="col-md-10">
                  <input type="text" class="form-control" placeholder="Description" name="option3[]">
                </div>
            </div>
            <button class="btn btn-primary" type="submit">Envoyer</button>
          </form>
        </div>
    </div>
    @isset($quote)
      <div class="mt-4">
        <table class="table">
          <tbody>
            <tr>
              <td>{{ $quote->created_at }}</td>
              <td><a target="_blank" href="{{ Storage::disk('s3')->url($quote->url) }}">{{ $quote->filename }}</a></td>
              @if($quote->accepted === 0)
                <td><a class="btn btn-success" href="{{ route('quote.accepted', $quote  ) }}">Accepter</a></td>
              @elseif($quote->accepted === 1)
                <td><a class="btn btn-warning" href="{{ route('quote.accepted', $quote  ) }}">Refuser</a></td>
              @endif
              
              <td><a class="btn btn-danger" href="{{ route('quote.delete', [$quote->id, $projet->id]  ) }}">Supprimer</a></td>

            </tr>
          </tbody>
        </table>
      </div>
    @endisset

    
  </div>


@endsection
