<?php

namespace App\Http\Controllers;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request){
        Stripe::setApiKey('sk_test_NDTXZMeG0rjCUfDlG00otvwf');

        $intent = PaymentIntent::create([
                'amount' => 1099,
                'currency' => 'eur',
                'payment_method_types' => ['card'],
                // Verify your integration in this guide by including this parameter
                'metadata' => ['integration_check' => 'accept_a_payment'],
                ]);
                
                $clientSecret = Arr::get($intent, 'client_secret');

            return view('payment.index', compact('clientSecret'));
        }
    
        
}

