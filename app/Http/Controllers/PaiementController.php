<?php

namespace App\Http\Controllers;


use Stripe\Charge;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\Session;

class PaiementController extends Controller
{
      /**
       * success response method.
       *
       * @return \Illuminate\Http\Response
       */
       public function index()
    {
        Stripe::setApiKey(env("STRIPE_SECRET"));

        $intent = PaymentIntent::create([
            'amount' => 1099,
            'currency' => 'eur',
            // Verify your integration in this guide by including this parameter
            'metadata' => ['integration_check' => 'accept_a_payment'],
        ]);


        $clientSecret = Arr::get($intent, 'client_secret');

        return view('payment.index', [
            'clientSecret' => $clientSecret,
            'intent' => $intent
        ]);
    }

    public function success() {
        $user = Auth::user();

        if ($user->step < 3){
                  $user->step = 3;
                  $user->save();
                }
        $step = $user->step;
      return route('home', compact('step'))->with('success','Votre acompte a bien été enregistré');
    }


}


