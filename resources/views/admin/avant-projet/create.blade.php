@extends('layouts.app')
@section('content')
  <div class="container">
    <div class="card" >
      <div class="card-header">
        <h4>Avant projet de {{ $user->firstname}} {{ $user->lastname}}</h4>
      </div>
      <div class="card-body">
          <form action="{{ route('admin.avant-projet.store', $user)}}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="selectProjet">Selectionner le projet</label>
                <select class="form-control" name="projetId">
                  <option value="{{ $projet->id }}">{{ $projet->description }}</option>
                </select>
              </div>
            <div class="row">
              <input name="userId" type="hidden" value="{{ $user->id }}">
              <div class="col-md-12 mb-3">
                <div class="form-group">
                  <div class="form-group">
                    <label for="avant-projet">Coller le lien de l'avant projet ici</label>
                    <textarea class="form-control" name="url" id="avant-projet" rows="3">
                      @if ($avant_projet)
                        {{ $avant_projet->url }}
                      @endif
                    </textarea>
                  </div>
                </div>
              </div>
            </div>
              <button class="btn btn-primary" type="submit">Envoyer</button>
              @if ($avant_projet)
                <a class="btn btn-danger" href="{{ route('avant-projet.delete', [$projet->id]  ) }}">Supprimer</a>
              @endif
              
          </form>
        </div>
    </div>
    @if ($avant_projet)
      {!! $avant_projet->url !!}
    @endif
  </div>

@endsection


