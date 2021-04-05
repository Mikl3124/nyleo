@extends('layouts.app')
@section('content')
  <div class="container-fluid row">
    <div class="col-sm-12 col-md-12">
      <div class="container">
        <h4 class="text-center">Votre devis</h4>
        <iframe src="{{ Storage::disk('s3')->url($quote->url) }}" width="100%" height="600px"></iframe>
        <div class="d-flex justify-content-between">
            <a href="{{ route('quote.download', $quote->id) }}">Télécharger votre devis</a>
            <a target="_blank" href="https://nyleo.fr/cgv/">Consulter nos CGV</a>
        </div>
        <div class="text-center mt-5">
            @if ($quote->accepted == 0)
              <button type="button" class="btn btn-primary mb-2" data-toggle="modal" data-target="#paymentModalCenter">
                Accepter le devis ({{ $quote->amount}}€)
              </button>
            @else
              <button type="button" class="btn btn-primary disabled mb-2">
                Vous avez déjà accepté le devis
              </button>
            @endif
        </div>
      </div>
    </div>
  </div>

  {{--   ----------- Modal Paiement-------------- --}}
    <div class="modal fade" id="paymentModalCenter" tabindex="-1" role="dialog" aria-labelledby="paymentModalCenterTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
             {{--   ----------- Si le devis contient des options-------------- --}}
          @if ($options->isnotempty())
            <form action="{{ route('pay-with-options', $quote->id) }}" method="post">
              @csrf
              <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLongTitle">Souhaitez-vous souscrire aux options?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                @foreach ($options as $option)
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="{{ $option->amount }}" id="flexCheckDefault" multiple="multiple" name="options[]"checked>
                    <label class="form-check-label" for="flexCheckDefault">
                    {{ $option->description }} (+{{ $option->amount }}€)
                    </label>
                  </div>
                @endforeach
              </div>
              <div class="text-center">
                <button class="btn btn-success" type="submit">Règler l'acompte</button>
              </div>
            </form>
        </div>
          {{--   ----------- Si le devis ne contient pas des options-------------- --}}
          @else
          <div class="modal-header">
            <h5 class="modal-title" id="paymentModalLongTitle">Règlement de {{ $display_amount }}€ (30% du devis)</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form action="#" class="my-4">
              <div id="card-element">
              <!-- Elements will create input elements here -->
              </div>
                    <!-- We'll put the error messages in this element -->
              <div id="card-errors" class="mb-3" role="alert"></div>
              <div class="text-center">
                  <button type="button" class="btn btn-secondary mt-3" data-dismiss="modal">Retour</button>
                  <button id="submit" class="btn btn-success mt-3" data-secret="<?= $intent->client_secret ?>">
                    Régler l'acompte
                    </button>
              </div>
            </form>
          </div>
              
          @endif
          
      </div>

@endsection

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
            color: "#aab7c4"
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
                    window.location.replace('/payment-success/{{ $quote->id }}');
                }
            }
        });
    });
  }
</script>

