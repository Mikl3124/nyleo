@extends('layouts.portal')
@section('title', 'Connexion')
@section('content')
<div class="card" style="max-width:560px;margin:20px auto">
    <h1>Accéder à mon espace</h1>
    <p class="muted">Pas de mot de passe à retenir : indiquez votre adresse e-mail, vous recevrez un lien pour vous connecter en un clic.</p>
    <form method="post" action="{{ route('portal.login.send') }}">
        @csrf
        <label for="email">Votre adresse e-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
        <p><button class="btn block" type="submit">Recevoir mon lien de connexion</button></p>
    </form>
</div>
@endsection
