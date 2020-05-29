@extends('layouts.app')
@section('content')
  <div class="container">
    @if($user->firstname)
      <h4 class="text-center">Fiche client de {{ $user->firstname }} {{ $user->lastname }}</h4>
    @else
      <h4 class="text-center">Fiche client de {{ $user->email }}</h4>
    @endif

    <a href="{{ route('admin.message.show', $user) }}" class="btn btn-primary">Messagerie</a>
    <a href="{{ route('admin.documents.show', $user) }}" class="btn btn-primary">Documents</a>
  </div>
@endsection
