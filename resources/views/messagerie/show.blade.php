@extends('layouts.app')

@section('content')
<div class="container-fluid row">
    <div class="col-sm-12 col-md-2">
      @include('layouts.steps')
    </div>
    <div class="col-sm-12 col-md-8">
        <div class="card" >
            <div class="card-header">
                <h4>Conversation avec Nyleo Conception </h4>
            </div>

                <div class="card-body scroll" id="messagesBox">
                    @if (isset($messages) && $messages->count())
                        @foreach ($messages as $message)
                            <div class="row mt-3">
                                <div class="col-md-10 {{ $message->to_id === $user->id ? 'offset-md-2 text-right' : ''}}">
                                    {{-- ------------- Si le contenu du message est vide (fichier à télécharger seulement) ---------------- --}}
                                    @if ($message->content === null)
                                        @isset($message->file_message)
                                            <p class="mb-0"><strong>{{ $message->to_id === $user->id ? 'Moi' : 'Nyleo Conception'}}</strong><span class="message_date"> Le {{ Carbon\Carbon::parse($message->created_at)->locale('fr')->isoFormat('dddd, Do MMMM, H:mm') }}</span> <br></p>
                                            <p class="{{ $message->to_id === $user->id ? 'my_message' : 'his_message'}}">
                                                <a href="{{ route('admin.messagerie.download', $message)}}"><i class="fas fa-download"></i> {{ $message->filename }}</a>
                                            </p>
                                        @endisset
                                    {{-- ------------- Si le contenu du message n'est pas vide ---------------- --}}
                                    @else
                                        <p class="mb-0"><strong>{{ $message->to_id === $user->id ? 'Moi' : 'Nyleo Conception'}}</strong><span class="message_date"> Le {{ Carbon\Carbon::parse($message->created_at)->isoFormat('Do MMMM YYYY à H:mm') }}</span><br></p>
                                        <p class="{{ $message->to_id === $user->id ? 'my_message' : 'his_message'}}">{!! nl2br(e($message->content)) !!}</p>
                                        @if ($message->content === null)
                                            @isset($message->file_message)
                                                <p class="{{ $message->to_id === $user->id ? 'my_message' : 'his_message'}}">
                                                    <a href="{{ route('admin.messagerie.download', $message)}}"><i class="fas fa-download"></i> {{ $message->filename }}</a>
                                                </p>
                                            @endisset
                                        @endif                                    
                                    @endif
                                        {{-- ------------- Si le message contient une pièce jointe ---------------- --}}
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
        <form action="{{ route('message.store')}}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group mt-4">
                <textarea class="form-control @error('content') is-invalid @enderror" placeholder="Ecrivez votre message..." name="content" rows="3"></textarea>
                @error('content')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>
            <div class="form-group">
                <input type="file" class="form-control-file @error('file_projet') is-invalid @enderror" id="file-message" value="{{ old('file-message') }}" name="file_message">
                @error('file_message')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <button class="btn btn-primary" type="submit">Envoyer</button>
        </form>
    </div>
    <div class="col-sm-12 col-md-2">
        @include('layouts.vertical-navbar')
    </div>
</div>

@endsection
