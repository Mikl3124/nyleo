<?php

namespace App\Http\Controllers;

use Stripe\Charge;
use Stripe\Stripe;
use Stripe\Customer;
use App\Mail\TestMail;
use App\Mail\SuccessPay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class DonateController extends Controller
{

    public function index(Request $request)
    {
        return view('payment.donate', [
            'amount' => $request->amount * 100,
            'description' => $request->description
        ]);
    }

    public function submit(Request $request)
    {
        try {
            $this->doPayment($request->stripeToken, $request->stripeEmail, $request->amount);
        } catch (\Exception $e) {
            return view('payment.error', compact('e'));
        }
        Mail::to($request->stripeEmail)->queue(new SuccessPay($request->amount));
        return view('payment.success');
    }

    protected function doPayment($token, $email, $amount)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $customer = Customer::create(array(
            'email' => $email,
            'card'  => $token
        ));

        $charge = Charge::create(array(
            'customer' => $customer->id,
            'amount'   => $amount,
            'currency' => 'eur'
        ));
    }
}
