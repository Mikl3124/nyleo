@extends('layouts.app')
@section('content')
  <div class="container-fluid row">
    <div class="col-sm-12 col-md-2">
      @include('layouts.steps')
    </div>
    <div class="col-sm-12 col-md-8">
        <h3 class="text-center mt-4">Vos documents</h3>
        <div class="row">
            @foreach ($documents as $document)
                @if (pathinfo($document->filename, PATHINFO_EXTENSION) == 'pdf')
                    <div class="text-center col-md-3 col-sm-12 my-auto">
                        <div>
                            <a href="{{ $document->url }}"><i class="fas fa-eye text-dark"></i></a>
                            <a onclick="return confirm('Voulez-vous vraiment supprimer ce document?')" href="{{ route('document.delete', $document) }}"><i class="fas fa-trash-alt text-danger"></i></a>
                        </div>
                        <a target="_blank" href="{{ $document->url }}">
                            <img class="img-fluid img-thumbnail" src="https://nyleo.s3.eu-west-3.amazonaws.com/pdf-icon.png">
                            <p> {{ $document->filename }} </p>
                        </a>
                    </div>
                @elseif(pathinfo($document->filename, PATHINFO_EXTENSION) == 'jpg')
                    <div class="text-center col-md-3 col-sm-12 my-auto">
                        <div>
                            <a href="{{ $document->url }}"><i class="fas fa-eye text-dark"></i></a>
                            <a onclick="return confirm('Voulez-vous vraiment supprimer ce document?')" href="{{ route('document.delete', $document) }}"><i class="fas fa-trash-alt text-danger"></i></a>
                        </div>
                        <a target="_blank" href="{{ $document->url }}">
                            <img class="img-fluid img-thumbnail" src="{{ $document->url }}">
                            <p> {{ $document->filename }} </p>
                        </a>
                    </div>
                @elseif(pathinfo($document->filename, PATHINFO_EXTENSION) == 'jpeg')
                    <div class="text-center col-md-3 col-sm-12 my-auto">
                        <div>
                            <a href="{{ $document->url }}"><i class="fas fa-eye text-dark"></i></a>
                            <a onclick="return confirm('Voulez-vous vraiment supprimer ce document?')" href="{{ route('document.delete', $document) }}"><i class="fas fa-trash-alt text-danger"></i></a>
                        </div>
                        <a target="_blank" href="{{ $document->url }}">
                            <img class="img-fluid img-thumbnail" src="{{ $document->url }}">
                            <p> {{ $document->filename }} </p>
                        </a>
                    </div>
                @elseif(pathinfo($document->filename, PATHINFO_EXTENSION) == 'png')
                    <div class="text-center col-md-3 col-sm-12 my-auto">
                        <div>
                            <a href="{{ $document->url }}"><i class="fas fa-eye text-dark"></i></a>
                            <a onclick="return confirm('Voulez-vous vraiment supprimer ce document?')" href="{{ route('document.delete', $document) }}"><i class="fas fa-trash-alt text-danger"></i></a>
                        </div>
                        <a target="_blank" href="{{ $document->url }}">
                            <img class="img-fluid img-thumbnail" src="{{ $document->url }}">
                            <p> {{ $document->filename }} </p>
                        </a>
                    </div>
                @elseif(pathinfo($document->filename, PATHINFO_EXTENSION) == 'zip')
                    <div class="text-center col-md-3 col-sm-12 my-auto">
                        <div>
                            <a href="{{ $document->url }}"><i class="fas fa-eye text-dark"></i></a>
                            <a onclick="return confirm('Voulez-vous vraiment supprimer ce document?')" href="{{ route('document.delete', $document) }}"><i class="fas fa-trash-alt text-danger"></i></a>
                        </div>
                        <a target="_blank" href="{{ $document->url }}">
                            <img class="img-fluid img-thumbnail" src=https://nyleo.s3.eu-west-3.amazonaws.com/zip-icon.png>
                            <p> {{ $document->filename }} </p>
                        </a>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
     <div class="col-sm-12 col-md-2">
       @include('layouts.vertical-navbar')
    </div>
  </div>

@endsection
