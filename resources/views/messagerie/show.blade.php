@extends('layouts.app')
@section('content')
  <div class="container-fluid row">
    <div class="col-sm-12 col-md-2">
      @include('layouts.steps')
    </div>
    <div class="col-sm-12 col-md-10">
      <div class="card" >
        <div class="card-header text-center">
          <h4>Messagerie</h4>
        </div>
        <div class="card-body scroll">
          @if (isset($messages))
            @foreach ($messages as $message)
              <p>{{ $message->content }}</p>
            @endforeach
          @endif
        </div>
    </div>
    <form action="{{ route('message.store')}}" method="post" enctype="multipart/form-data">
      @csrf
      <div class="form-group mt-4">
          <textarea class="form-control @error('content') is-invalid @enderror" placeholder="Votre message privé..." name="content" rows="3"></textarea>
          @error('content')
              <div class="invalid-feedback">
                  {{ $message }}
              </div>
          @enderror
      </div>
      <button class="btn btn-primary" type="submit">Envoyer</button>
    </form>
    <iframe src="https://cdn.flipsnack.com/widget/v2/widget.html?hash=fuia695jd" width="100%" height="540" seamless="seamless" scrolling="no" frameBorder="0" allowFullScreen></iframe>
  </div>


@endsection
