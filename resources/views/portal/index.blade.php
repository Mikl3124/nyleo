@extends('layouts.portal')
@section('title', 'Mes dossiers')
@section('content')
<h1>Bonjour {{ $client->first_name ?: $client->display_name }}</h1>
<p class="muted">Voici vos projets en cours.</p>
@forelse ($dossiers as $d)
    <div class="card">
        <h2 style="margin-bottom:4px">{{ $d->title }}</h2>
        <p class="muted" style="margin-top:0">{{ $d->procedure->label() }} — {{ $d->status->label() }}</p>
        <a class="btn" href="{{ route('portal.dossiers.show', $d) }}">Voir ce dossier</a>
    </div>
@empty
    <div class="card"><p>Aucun dossier pour le moment.</p></div>
@endforelse
@endsection
