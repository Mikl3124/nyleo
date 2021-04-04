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
    <a href="{{ route('avant-projet.create', $user) }}" class="btn btn-primary">Avant-projet</a>
    @if (isset($options))
      <div class="mt-5">
        <h4>Choix des options du client:</h4>
        <ul>
            @foreach ($options as $option)
            <li>{{ $option->description }} {{ $option->amount }}€</li>
        </ul>     
          @endforeach
      </div>
    @endif

  </div>



@endsection


</div>

