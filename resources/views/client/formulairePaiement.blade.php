<html>
<head>
    <title>Mon application - Paiement par carte bancaire</title>
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <style>
        label {
            color: #797D88;
        }
        .group {
            background: white;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .cardCustom label {
            position: relative;
            color: #8898AA;
            font-weight: 300;
            height: 40px;
            line-height: 40px;
            display: flex;
            flex-direction: row;
        }
        label > span {
            width: 80px;
            text-align: right;
            margin-right: 30px;
        }
        .cardCustom .field {
            background: transparent;
            font-weight: 300;
            border: 0;
            color: #31325F;
            outline: none;
            flex: 1;
            padding-right: 10px;
            padding-left: 10px;
            cursor: text;
        }
        .field::-webkit-input-placeholder { color: #CFD7E0; }
        .field::-moz-placeholder { color: #CFD7E0; }
        button {
            float: left;
            display: block;
            background: #666EE8;
            color: white;
            box-shadow: 0 7px 14px 0 rgba(49,49,93,0.10),
            0 3px 6px 0 rgba(0,0,0,0.08);
            border-radius: 4px;
            border: 0;
            margin-top: 20px;
            font-size: 15px;
            font-weight: 400;
            width: 100%;
            height: 40px;
            line-height: 38px;
            outline: none;
        }
        button:focus {
            background: #555ABF;
        }
        button:active {
            background: #43458B;
        }
        .outcome {
            float: left;
            width: 100%;
            padding-top: 8px;
            min-height: 24px;
            text-align: center;
        }
        .success, .error {
            display: none;
            font-size: 13px;
        }
        .success.visible, .error.visible {
            display: inline;
        }
        .error {
            color: #E4584C;
        }
        .success {
            color: #666EE8;
        }
        .success .token {
            font-weight: 500;
            font-size: 13px;
        }
    </style>
</head>
<body>
<div class="container">
    <form action="/api/paiement" method="post" id="formPayment">
        <div class="row" style="margin-top: 80px; padding-bottom: 30px">
            <div class="col-2"></div>
            <div class="col-md-8" style="padding: 0 55px">
                <div class="text-center" style="padding: 11px;background-color: #E5F1FF;">
                    <span style="font-weight: 500; font-size: 17px">Paiement de l'acompte 30%</span><br>
                    <span style="font-size: 22px; font-weight: 800; color: #338BF8">{{ ($quote->amount)*30/100 }} €</span>
                </div>
                <h2 style="color: #338BF8; font-weight: 800; font-size: 15px; margin-bottom: 28px; margin-top: 20px">INFORMATIONS DE FACTURATION</h2>
                <div class="row" style="margin-top: 20px;">
                    <div class="form-group col-md-6 group">
                        <label for="nomprenom" style="font-weight: 500">Nom et prénom</label>
                    <input style="font-size: 16px;" type="text" name="cardholder-name" id="nomprenom" class="field onlyBorderBottom form-control" value="{{ Auth::user()->lastname }} {{ Auth::user()->firstname }}" required/>
                    </div>
                    <div class="form-group col-md-6 group">
                        <label for="email" style="font-weight: 500">Adresse email</label>
                        <input style="font-size: 16px;" type="email" name="cardholder-email" id="email" class="field onlyBorderBottom form-control" value="{{ Auth::user()->email }}" required/>
                    </div>
                </div>

                <div class="row" style="margin-top: 20px;">
                    <div class="form-group col-md-6 group">
                        <label for="adresse" style="font-weight: 500">Adresse</label>
                        <input style="font-size: 16px;" type="text" name="cardholder-address" id="adresse" class="field onlyBorderBottom form-control" value="{{ Auth::user()->address }}" required/>
                    </div>
                    <div class="form-group col-md-6 group">
                        <label for="ville" style="font-weight: 500">Ville</label>
                        <input style="font-size: 16px;" type="text" name="cardholder-city" id="ville" class="field onlyBorderBottom form-control" value="{{ Auth::user()->town }}" required/>
                    </div>
                </div>

                <h2 style="color: #338BF8; font-weight: 800; font-size: 15px; margin-bottom: 28px; margin-top: 30px">CARTE BANCAIRE</h2>
                <div class="cardCustom">
                    <div class="group" style="border: none; border-bottom:1px solid #caccd0; border-radius: 0 ">
                        <label>
                            <div id="card-element" class="field"></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" id="stripeToken" name="stripeToken">
        <input type="submit" id="submitButton" >
        <div class="outcome">
            <div class="error"></div>
        </div>
    </form>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
    var stripe = Stripe('pk_test_igBQDOGCawrdKCBQPqG5C37X');
    var elements = stripe.elements();
    var card = elements.create('card', {
        style: {
            base: {
                iconColor: '#666EE8',
                color: '#31325F',
                lineHeight: '40px',
                fontWeight: 300,
                fontFamily: 'Helvetica Neue',
                fontSize: '15px',

                '::placeholder': {
                    color: '#CFD7E0',
                },
            },
        }
    });
    card.mount('#card-element');

    function setOutcome(result) {
        var errorElement = document.querySelector('.error');
        errorElement.classList.remove('visible');

        if (result.token) {
            $('#stripeToken').val(result.token.id);
            $('#formPayment').submit();
        } else if (result.error) {
            errorElement.textContent = result.error.message;
            errorElement.classList.add('visible');
        }
    }

    card.on('change', function(event) {
        setOutcome(event);
    });

    document.getElementById('formPayment').addEventListener('submit', function(e) {
        e.preventDefault();

        var form = document.getElementById('formPayment');
        stripe.createPaymentMethod('card', card, {
            billing_details: {name: form.querySelector('input[name=cardholder-name]').value}
        }).then(function(result) {
            if (result.error) {
                var errorElement = document.querySelector('.error');
                errorElement.textContent = result.error.message;
                errorElement.classList.add('visible');
            } else {
                $('#buttonPayment').hide();
                $('#spanWaitPayement').show();
                // Otherwise send paymentMethod.id to your server (see Step 2)
                fetch('/api/paiement', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    },
                    body: JSON.stringify({ payment_method_id: result.paymentMethod.id })
                }).then(function(result) {
                    result.json().then(function(json) {
                        handleServerResponse(json);
                    })
                });
            }
        });
    });

    function handleServerResponse(response) {
        if (response.error) {
            $('#buttonPayment').show();
            $('#spanWaitPayement').hide();
            var errorElement = document.querySelector('.error');
            errorElement.textContent = result.error.message;
            errorElement.classList.add('visible');
        } else if (response.requires_action) {
            // Use Stripe.js to handle required card action
            stripe.handleCardAction(
                response.payment_intent_client_secret
            ).then(function(result) {
                if (result.error) {
                    $('#buttonPayment').show();
                    $('#spanWaitPayement').hide();
                    var errorElement = document.querySelector('.error');
                    errorElement.textContent = result.error.message;
                    errorElement.classList.add('visible');
                } else {
                    // The card action has been handled
                    // The PaymentIntent can be confirmed again on the server
                    fetch('/api/paiement', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        },
                        body: JSON.stringify({ payment_intent_id: result.paymentIntent.id })
                    }).then(function(confirmResult) {
                        return confirmResult.json();
                    }).then(handleServerResponse);
                }
            });
        } else {
            window.location.replace('/paiement-ok');
        }
    }
</script>
</body>
</html>