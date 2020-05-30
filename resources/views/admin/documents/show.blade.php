@extends('layouts.app')
@section('content')
  <div class="container">
    <h4>Liste des documents</h4>
    @foreach ($documents as $document)

     <p><a href="{{ $document->url }}">{{ $document->filename }}</a></p>

    @endforeach
  </div>
@endsection
