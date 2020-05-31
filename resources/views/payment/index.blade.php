<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>
    </head>
    <body>
        <form action="{{ route('stripe.post')}}" method="POST">
            @csrf
            <script
                    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
                    data-key="pk_test_AXgmaDPOIAiU7iU58igNYqzu002VjpQ5fz"
                    data-amount="38700"
                    data-name="Stripe Demo"
                    data-description="Online course about integrating Stripe"
                    data-image="https://stripe.com/img/documentation/checkout/marketplace.png"
                    data-locale="auto"
                    data-currency="eur">
            </script>
        </form>
    </body>
</html>