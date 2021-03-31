<!DOCTYPE html>
<html>
    <head>
        <title>Paiement Nyleo</title>
        <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Nyleo Conception') }}</title>

        <!-- Scripts -->

        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
        <script src="{{ asset('js/app.js') }}" defer></script>

        @yield('extra-script')

        <!-- Fonts -->
        <link href='https://api.mapbox.com/mapbox-gl-js/v1.10.1/mapbox-gl.css' rel='stylesheet' />
        <link rel="dns-prefetch" href="//fonts.gstatic.com">
        <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css">
        <!-- Styles -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">



    </head>
<body>
<div class="container mt-5">
    <div class="content">
        <div class="image">
            <img src="https://nyleo.s3.eu-west-3.amazonaws.com/logo_nyleo_128.png" />
        </div>
        <h1 class="text-danger">Désolé...</h1>

        <p>Le paiement n'a pas fonctionné, le serveur a renvoyé cette erreur "{{ $e->getMessage() }}"</span> </p>
        <a class="btn btn-outline-success" href="https://clients.nyleo.fr/paiement">Essayer à nouveau</a>
        
    </div>
</div>