@extends('layouts.portal')
@section('title', 'Mes dossiers')
@section('content')
<h1>Bonjour {{ $client->first_name ?: $client->display_name }}</h1>
<p class="muted">Voici vos projets. Touchez un projet pour voir où il en est.</p>
@forelse ($dossiers as $d)
    <div class="card {{ $d->client_todo ? 'action' : '' }}">
        @if ($d->client_todo)<span class="todo-tag">À faire : {{ mb_strtolower($d->client_todo) }}</span>@endif
        <h2 style="margin-bottom:4px">{{ $d->title }}</h2>
        <p class="muted" style="margin-top:0">{{ $d->procedure->label() }} — {{ $d->clientStatusMessage() }}</p>
        <a class="btn {{ $loop->first && $d->client_todo ? 'cta' : '' }} block" href="{{ route('portal.dossiers.show', $d) }}">Voir ce dossier</a>
    </div>
@empty
    <div class="card"><p>Aucun dossier pour le moment.</p></div>
@endforelse
@endsection
