@extends('layouts.app')
@section('content')
  <div class="container">
    <div class="card" >
        <div class="card-header">
          <h4>Conversation avec {{ $user->firstname}} {{ $user->lastname}}</h4>
        </div>

        <div class="card-body scroll" id="messagesBox">
            @if (isset($messages) && $messages->count())
                @foreach ($messages as $message)
                    <div class="row mt-3">
                        <div class="col-md-10 {{ $message->from_id != $user->id ? 'offset-md-2 text-right' : ''}}">
                            {{-- ------------- Si le contenu du message est vide (fichier à télécharger seulement) ---------------- --}}
                            @if ($message->content === null)
                                @isset($message->file_message)
                                    <p class="mb-0"><strong>{{ $message->from_id != $user->id ? 'Moi' : $user->firstname}}</strong><span class="message_date"> Le {{ Carbon\Carbon::parse($message->created_at)->isoFormat('Do MMMM YYYY à h:mm') }}</span> <br></p>
                                    <p class="{{ $message->from_id != $user->id ? 'my_message' : 'his_message'}}">
                                        @if ($message->read_at != NULL && $message->from_id != $user->id) <small><i class='fas fa-check text-success'></i></small> @endif
                                        <a href="{{ route('admin.messagerie.download', $message)}}"><i class="fas fa-download"></i> {{ $message->filename  }}</a>
                                    </p>
                                @endisset
                            {{-- ------------- Si le contenu du message n'est pas vide ---------------- --}}
                            @else
                                <p class="mb-0"><strong>{{ $message->from_id != $user->id ? 'Moi' : $user->firstname}}</strong><span class="message_date"> Le {{ Carbon\Carbon::parse($message->created_at)->isoFormat('Do MMMM YYYY à h:mm') }}</span> <br></p>
                                <p class="{{ $message->from_id != $user->id ? 'my_message' : 'his_message'}}">@if ($message->read_at != NULL && $message->from_id != $user->id) <small><i class='fas fa-check text-success'></i></small> @endif {!! nl2br(e($message->content)) !!}  </p>
                                {{-- ------------- Si le message contient une pièce jointe ---------------- --}}
                                @isset($message->file_message)
                                    @if ($message->content != null)
                                        <p class="{{ $message->from_id != $user->id ? 'my_message' : 'his_message'}}">
                                            @if ($message->read_at != NULL && $message->from_id != $user->id) <small><i class='fas fa-check text-success'></i></small> @endif
                                            <a href="{{ route('admin.messagerie.download', $message)}}"><i class="fas fa-download"></i> {{ $message->filename }}</a>
                                        </p>
                                    @endif
                                @endisset
                            @endif


                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center">
                    <em>Aucun message à afficher</em>
                </div>
            @endif
        </div>
    </div>
    <form action="{{ route('admin.message.store', $user)}}" method="post" enctype="multipart/form-data">
      @csrf
      <div class="form-group mt-4">
          <textarea class="form-control @error('content') is-invalid @enderror" placeholder="Votre message privé..." name="content" rows="3"></textarea>
          @error('content')
              <div class="invalid-feedback">
                  {{ $message }}
              </div>
          @enderror
          <input name="to" type="hidden" value="{{ $user->id }}">
      </div>
      <!-- ---------------- Upload ------------------ -->
      <div class="form-group">
          <input type="file" class="form-control-file @error('file_projet') is-invalid @enderror" id="file-message" value="{{ old('file-message') }}" name="file_message">
          @error('file_message')
          <div class="invalid-feedback">{{ $message }}</div>
          @enderror
      </div>
      <button class="btn btn-primary" type="submit">Envoyer</button>
    </form>
  </div>

<scroll-top></scroll-top>
@endsection
