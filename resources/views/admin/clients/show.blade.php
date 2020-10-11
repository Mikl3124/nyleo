@extends('layouts.app')
@section('content')
  <div class="container">
    @if($user->firstname)
      <h4 class="text-center">Fiche client de {{ $user->firstname }} {{ $user->lastname }}</h4>
    @else
      <h4 class="text-center">Fiche client de {{ $user->email }}</h4>
    @endif

    <a href="{{ route('admin.message.show', $user) }}" class="btn btn-primary">Messagerie</a>
    <a href="{{ route('admin.documents.show', $user) }}" class="btn btn-primary">Consulter les documents</a>
    <a href="{{ route('admin.upload.page', $user) }}" class="btn btn-primary">Envoyer un document</a>
    <a href="{{ route('devis.create', $user) }}" class="btn btn-primary">Devis</a>
    <a href="{{ route('avantProjet.create', $user) }}" class="btn btn-primary">Envoyer l'avant projet</a>
    <div class="text-right">
      <button type="button" class="btn btn-success" data-toggle="modal" data-target="#exampleModal">
        Assigner un dessinateur
      </button>
    </div>
    
  </div>

@endsection

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Liste des dessinateurs</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        ...
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>