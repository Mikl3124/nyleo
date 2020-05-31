@extends('layouts.app')

@section('extra-script')
    <script src="https://js.stripe.com/v3/"></script>
@endsection

@section('content')
    <div class="col-md-12"></div>
        <h1>Page de paiement</h1>
        <div class="row">
            <div class="col-md-6">
                <form action="#" class="my-4" id="payment-form">
                    <div id="card-element">
                        <!-- Elements will create input elements here -->
                    </div>

                    <!-- We'll put the error messages in this element -->
                    <div id="card-errors" role="alert"></div>

                    <button class="btn btn-success mt-4" id="submit">Procéder au paiment</button>
                </form>
            </div>
        </div>
    </div>
<stripe-checkout></stripe-checkout>
@endsection


