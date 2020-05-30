<?php

namespace App\Http\Controllers;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request){
        if($request->payment_method === 'stripe'){
            Stripe::setApiKey('sk_test_NDTXZMeG0rjCUfDlG00otvwf');

            $intent = PaymentIntent::create([
                'amount' => 1099,
                'currency' => 'eur',
                ]);
            
                $clientSecret = Arr::get($intent, 'client_secret');

            return view('payment.index', compact('clientSecret'));
        }
        
    }
}
