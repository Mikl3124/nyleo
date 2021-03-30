<!DOCTYPE html>
<html>
<head>
    <title>Laravel</title>
    <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">
    <link href="/css/style.css" rel="stylesheet" type="text/css">
</head>
<body>
<div class="container">
    <div class="content">
        <div class="image">
            <img src="https:///nyleo.fr/wp-content/uploads/2015/02/Logo-nyleoxhdpi.png" />
        </div>
        <h1>Nyleo Conception</h1>
        @if ($amount)
        <p>
            Vous avez décider d'envoyer la somme de {{ $amount / 100}}€ à Nyleo Conception.
        </p>
        @endif

        @if (! $amount)
            <form action="/donate" method="GET">
                <div class="form-item">
                    <label for="amount">Saisissez la somme:</label>
                    <input type="text" name="amount" value="0">
                </div>
                <p>
                <button type="submit">
                    <span style="display: block; min-height: 30px;">Continuer</span>
                </button>
                </p>
            </form>
        @else
            <form action="/donate" method="POST">
                {{ csrf_field() }}
                <input type="hidden" name="amount" value="{{ $amount }}">
                <script
                        src="https://checkout.stripe.com/checkout.js" class="stripe-button"
                        data-key="{{ config('services.stripe.key') }}"
                        data-amount="{{ $amount }}"
                        data-name="Paiement Sécurisé"
                        data-description="{{ $description or "Nyleo Conception" }}"
                        {{--data-image="/128x128.png"--}}
                        data-locale="auto"
                        data-currency="eur"
                        data-allow-remember-me="false"
                        data-label="Confirmer">
                </script>
            </form>
        @endif
    </div>
</div>

<script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>

</body>
</html>
