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
<div class="container-donate mt-5">
    <div class="content mt-5">
        <div class="image">
            <img src="https://nyleo.s3.eu-west-3.amazonaws.com/logo_nyleo_128.png" />
        </div>

        @if (! $amount)
            <form action="/paiement" method="GET">
                <label for="amount">Saisissez la somme que vous souhaitez régler à Nyleo Conception:</label>
                <div class="form-row">
                    <div class="col-md-4 mb-3">

                    </div>
                    <div class="col-md-4 mb-3">

                        <div class="input-group mb-3">
                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" value="0">
                        <div class="input-group-append">
                            <span class="input-group-text">€</span>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">

                    </div>
                </div>
    </div>
                <p>
                <button type="submit" class="btn btn-outline-success">Continuer</button>
            </form>
        @else
            <form action="/paiement" method="POST">
                {{ csrf_field() }}
                <input type="hidden" name="amount" value="{{ $amount }}">
                <script
                        src="https://checkout.stripe.com/checkout.js" class="stripe-button"
                        data-key="{{ config('services.stripe.key') }}"
                        data-amount="{{ $amount }}"
                        data-description="Nyleo Conception"
                        {{--data-image="/128x128.png"--}}
                        data-image="https://nyleo.s3.eu-west-3.amazonaws.com/logo_nyleo_128.png"
                        data-locale="auto"
                        data-currency="eur"
                        data-allow-remember-me="false"
                        data-label="Confirmer">
                </script>
                <script>
                    // Hide default stripe button, be careful there if you
                    // have more than 1 button of that class
                    document.getElementsByClassName("stripe-button-el")[0].hidden=true;
                </script>
                <p>Confirmez-vous le paiement de {{ $amount / 100}}€ à Nyleo Conception?</p>
                <a class="btn btn-outline-secondary" href="javascript:history.back()">Retour</a>
                <button type="submit" class="btn btn-success">Confirmer</button>
            </form>
        @endif
    </div>
</div>



<script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>

</body>
</html>
