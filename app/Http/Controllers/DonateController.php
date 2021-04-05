<?php

namespace App\Http\Controllers;


use Stripe\Charge;
use Stripe\Stripe;
use App\Model\Quote;
use Stripe\Customer;
use App\Model\Option;
use App\Model\Paiement;
use Stripe\PaymentIntent;
use App\Mail\QuoteAccepted;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Mail\ConfirmPaiementToUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\Session;

class DonateController extends Controller
{
  /**
   * success response method.
   *
   * @return \Illuminate\Http\Response
   */

  public function pay(Request $request)
  {

    $total = $request->total * 100;
    $customer = $request->customer;

    Stripe::setApiKey(env("STRIPE_SECRET"));
    $intent = PaymentIntent::create([
      'amount' => $total,
      'currency' => 'eur',
      // Verify your integration in this guide by including this parameter
      'metadata' => ['integration_check' => 'accept_a_payment'],
    ]);

    $clientSecret = Arr::get($intent, 'client_secret');

    return view(
      'payment.donate',
      [
        'clientSecret' => $clientSecret,
        'intent' => $intent,
        'total' => $total,
        'customer' => $customer,
        'quote' => 1
      ]
    );
  }

  public function success()
  {
    return view('payment.success');
  }

  public function index()
  {
    return view('payment.success');
  }
}
