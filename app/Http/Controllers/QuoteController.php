<?php

namespace App\Http\Controllers;

use Stripe\Stripe;
use App\Model\File;
use App\Model\User;
use App\Model\Quote;
use App\Model\Projet;
use Stripe\PaymentIntent;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;

class QuoteController extends Controller
{
    public function quoteShow()
    {
          $user = Auth::user();
          $step = $user->step;
          $quote = Quote::where('user_id', '=', $user->id)->orderBy('created_at', 'desc')->first();
          $acount_amount = (round($quote->amount * 30 /100, 0) * 100);
          $display_amount = round($acount_amount / 100);
          $customer = Auth::user()->firstname . ' ' . Auth::user()->lastname;

          Stripe::setApiKey(env("STRIPE_SECRET"));

          $intent = PaymentIntent::create([
              'amount' => $acount_amount,
              'currency' => 'eur',
              // Verify your integration in this guide by including this parameter
              'metadata' => ['integration_check' => 'accept_a_payment'],
          ]);

          $clientSecret = Arr::get($intent, 'client_secret');

          return view('client.quote-show', compact('step', 'quote', 'clientSecret', 'intent', 'customer', 'display_amount'));

    }

    public function createQuote($id)
    {
      $user = User::find($id);
      $projets = Projet::where('user_id', '=', $user->id )->get();
      return view('admin.quote.create', compact('user', 'projets'));
    }

    public function storeQuote(Request $request)
    {

      $quote = new Quote;
      $user = User::find($request->userId);
      $quote->amount = $request->amount;
      $quote->user_id = $user->id;
      $quote->projet_id = $request->projetId;
      $projet = Projet::where('user_id', '=', $user->id)->orderBy('created_at', 'desc')->first();
      $projet->as_quote = 1;
      $projet->save();

        if ($files = $request->file('quoteFile')) {

            $filenamewithextension = $request->file('quoteFile')->getClientOriginalName();

            //get filename without extension
            $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);

            //get file extension
            $extension = $request->file('quoteFile')->getClientOriginalExtension();

            //filename to store
            //$path = 'documents/' . $user->lastname. '_' . $user->firstname . '_' . time();

            $filenametostore = $filename.'_'.time().'.'.$extension;


            $filename = $files->storeAs(
                'documents', $filenametostore
            );
              File::create([
                'user_id' => $user->id,
                'url' => Storage::disk('s3')->url('documents/' . $filenametostore),
                'filename' => $filenamewithextension
              ]);
            //Store $filenametostore in the database
            $quote->url = $filename;
            $quote->filename = $filenamewithextension;
            }
        $quote->save();

        // Notification


        return view('admin.clients.show', compact('user'));
    }

    public function downloadQuote($quote)
    {

        $dl = Quote::find($quote);
        return Storage::download($dl->url);

    }

    public function validationQuote(Request $request)
    {
        if($request->payment_method === 'stripe'){
            $quote = Quote::find($request->quote);
            return view('client.stripe', compact('quote'));
        }
    }


}
