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
use Illuminate\Support\Facades\Redirect;

class PaiementController extends Controller
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function with_options(Request $request, $quote)
    {
        $quote = Quote::find($quote);
        $result = 0;
        $customer = Auth::user()->firstname . ' ' . Auth::user()->lastname;
        if ($request->options) {
            foreach ($request->options as $option) {
                $result += $option;
            }
        }
        $total = ((int)(($quote->amount + $result) * 30));

        Stripe::setApiKey(env("STRIPE_SECRET"));
        $intent = PaymentIntent::create([
            'amount' => $total,
            'currency' => 'eur',
            // Verify your integration in this guide by including this parameter
            'metadata' => ['integration_check' => 'accept_a_payment'],
        ]);

        $clientSecret = Arr::get($intent, 'client_secret');

        return view('payment.payquote', [
            'clientSecret' => $clientSecret,
            'intent' => $intent,
            'total' => $total,
            'acount' => $total * 30,
            'amount' =>  $total * 100,
            'customer' => $customer,
            'quote' => $quote,
        ]);
    }

    public function success($quote)
    {
        $quote = Quote::find($quote);
        $options = Option::where('quote_id', $quote->id)->get();
        $amount = 0;
        if ($options) {
            foreach ($options as $option) {
                $amount += $option->amount;
            }
        }
        $total = $amount + $quote->amount;

        $user = Auth::user();
        if ($user->step < 3) {
            $user->step = 3;
            $user->save();
            $acount = $total * 30 / 100;
            $quote->accepted = 1;
            $quote->save();
            $paiement = new Paiement;
            $paiement->user_id = $user->id;
            $paiement->amount = $acount;
            $paiement->quote_id = $quote->id;
            $paiement->save();
        }
        $step = $user->step;
        Mail::to(env("MAIL_ADMIN"))
            ->send(new QuoteAccepted($user));

        Mail::to($user->email)
            ->send(new ConfirmPaiementToUser($paiement->amount));

        return Redirect::back()->with('success', "Votre règlement de {{ $paiement->amount }}€ a bien été enregistré");
    }

    public function payAvantProjet(Request $request, $quote)
    {
        $quote = Quote::find($quote);
        $quote_amount = $quote->amount;
        $pay_amount = $request->pay_amount;
        $total = $quote_amount - $pay_amount;
        $customer = Auth::user();


        Stripe::setApiKey(env("STRIPE_SECRET"));
        $intent = PaymentIntent::create([
            'amount' => $total * 100,
            'currency' => 'eur',
            // Verify your integration in this guide by including this parameter
            'metadata' => ['integration_check' => 'accept_a_payment'],
        ]);


        $clientSecret = Arr::get($intent, 'client_secret');

        return view('payment.pay-avant-projet', [
            'clientSecret' => $clientSecret,
            'total' => $total * 100,
            'intent' => $intent,
            'customer' => $customer->firstname . ' ' . Auth::user()->lastname,
            'quote' => $quote,
        ]);
    }

    public function successPayAvantProjet($quote)
    {

        $quote = Quote::find($quote);
        $paiement = Paiement::where('quote_id', $quote->id)->first();
        $total = ($quote->amount) - ($paiement->amount);

        $user = Auth::user();
        if ($user->step < 4) {
            $user->step = 4;
            $user->save();
            $paiement = new Paiement;
            $paiement->user_id = $user->id;
            $paiement->amount = $total;
            $paiement->quote_id = $quote->id;
            $paiement->save();
        }
        $step = $user->step;
        Mail::to(env("MAIL_ADMIN"))
            ->send(new QuoteAccepted($user));

        Mail::to($user->email)
            ->send(new ConfirmPaiementToUser($total));
        return redirect()->route('home', compact('step'))->with('success', "Votre règlement de {{ $total }}€ a bien été enregistré");
    }

    public function successPay()
    {
        $user = "coco@gmail.com";

        Mail::to(env("MAIL_ADMIN"))
            ->send(new QuoteAccepted($user));

        Mail::to('coco@gmail.com')
            ->send(new ConfirmPaiementToUser());
        return view('payment.success');
    }
}
