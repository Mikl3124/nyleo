@extends('layouts.app')

@section('content')
  <div class="container-fluid">
    @include('layouts.steps')

    <div class="container">
       <div class="container">
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
        <div class="file-chooser">
          <input type="file" class="file-chooser__input" id="file5" name="file5[]">
        </div>
        <button type="submit" class="btn btn-primary">Envoyer</button>
      </form>
    </div>



@endsection
