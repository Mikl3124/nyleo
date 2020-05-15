@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class=text-center>DASHBOARD</h1>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#CreateUserModal">
      Créer un client
    </button>
</div>

@endsection

<!-- Modal Création Client-->
<div class="modal fade" id="CreateUserModal" tabindex="-1" role="dialog" aria-labelledby="CreateUserModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="CreateUserModalLabel">Créer un utilisateur</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('client.store') }}">
            @csrf
            <div class="form-group">
                <input placeholder="Adresse e-mail" id="email" type="email" class="form-control" name="email" required autocomplete="email">
            </div>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-success">Créer</button>
        </form>
      </div>
    </div>
  </div>
</div>
