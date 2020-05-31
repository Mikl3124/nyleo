<?php

namespace App\Http\Controllers;


use Stripe\Charge;
use Stripe\Stripe;
use Stripe\Customer;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class PaiementController extends Controller
{
     /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function stripe()
    {
        return view('payment.index');
    }
  
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function stripePost(Request $request)
    {
       try {
            Stripe::setApiKey('sk_test_R9JQ3Uw7BMGfM6OVva8knxqU00lAC5E45E');

            $customer = Customer::create(array(
                'email' => $request->stripeEmail,
                'source' => $request->stripeToken
            ));

            $charge = Charge::create(array(
                'customer' => $customer->id,
                'amount' => 20000,
                'currency' => 'eur'
            ));

            return 'Charge successful, you get the course!';
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }
}

