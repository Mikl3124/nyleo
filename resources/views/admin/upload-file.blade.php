@extends('layouts.app')

@section('content')
  <div class="container-fluid row">
    <div class="col-sm-12 col-md-12">
    <h4 class=text-center>Client: {{ $user->firstname }} {{ $user->lastname }}</h4>
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
        <div class="file-chooser ">
          <input type="file" class="file-chooser__input" id="file5" name="file5[]">
          <input type="hidden" name="userId" value="{{ $user->id }}">
        </div>
        <div class="text-center">
          <a class="btn btn-secondary mt-3" href="{{ route('home')}}">Annuler</a>
          <button type="submit" class="btn btn-primary mt-3">Envoyer</button>
        </div>

      </form>
    </div>

@endsection
