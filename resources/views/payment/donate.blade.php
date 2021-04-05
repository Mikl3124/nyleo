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
    <div class="container">
        <div class="mt-5">
            <h3 class="display-4 text-center"><i class="fab fa-cc-visa"></i> Paiement sécurisé <i class="fab fa-cc-visa"></i></h3>
            <hr class="my-4">
            <p class="lead text-center">Veuillez saisir vos informations, afin de régler {{ number_format((float)$total/100, 2, '.', '') }}€ à Nyleo Conception </p>
            
            <form action="#" class="my-4">
                    <div id="card-element" class="p-3 border border-secondary rounded mx-5">
                    <!-- Elements will create input elements here -->
                    </div>
                            <!-- We'll put the error messages in this element -->
                    <div id="card-errors" class="mb-3" role="alert"></div>
                    <div class="text-center">
                      <a href="{{ url()->previous() }}" class="btn btn-secondary mt-3" >Retour</a>
                        <button id="submit" class="btn btn-success mt-3" data-secret="<?= $intent->client_secret ?>">
                            Payer
                        </button>
                    </div>
                </form>
        </div>
    </div>
</body>


  <script src="https://js.stripe.com/v3/"></script>
<script>
  window.onload = function(){
    var stripe = Stripe('{{ env('STRIPE_KEY') }}');;
    var elements = stripe.elements();
    var style = {
        base: {
        color: "#32325d",
        fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
        fontSmoothing: "antialiased",
        fontSize: "16px",
        "::placeholder": {
            color: ""
        }
        },
        invalid: {
        color: "#dc3545",
        iconColor: "#dc3545"
        }
    };
    var card = elements.create("card", {
      hidePostalCode: true,
      style: style });
    card.mount("#card-element");
    card.addEventListener('change', ({error}) => {
    const displayError = document.getElementById('card-errors');
        if (error) {
            displayError.classList.add('alert', 'alert-danger', 'mt-3');
            displayError.textContent = error.message;
        } else {
            displayError.classList.remove('alert', 'alert-danger', 'mt-3');
            displayError.textContent = '';
        }
    });
    var submitButton = document.getElementById('submit');
    submitButton.addEventListener('click', function(ev) {
    ev.preventDefault();
    stripe.confirmCardPayment("{{ $clientSecret }}", {
      payment_method: {
        card: card,
        billing_details: {
          name: '{{ $customer }}'
        }
      }
        }).then(function(result) {
            if (result.error) {
            // Show error to your customer (e.g., insufficient funds)
            console.log(result.error.message);
            window.alert(result.error.message);
            } else {
                // The payment has been processed!
                if (result.paymentIntent.status === 'succeeded') {
                    // Show a success message to your customer
                    // There's a risk of the customer closing the window before callback
                    // execution. Set up a webhook or plugin to listen for the
                    // payment_intent.succeeded event that handles any business critical
                    // post-payment actions.
                    console.log(result.paymentIntent);
                    window.location.replace('{{ route ('success-paiement') }}');
                }
            }
        });
    });
  }
</script>


</body>
</html>
