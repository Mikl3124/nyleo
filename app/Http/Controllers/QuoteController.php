<?php

namespace App\Http\Controllers;

use Stripe\Stripe;
use App\Model\File;
use App\Model\User;
use App\Model\Quote;
use App\Model\Option;
use App\Model\Projet;
use Stripe\PaymentIntent;
use Illuminate\Support\Arr;
use App\Jobs\NewQuoteCreate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    $options = Option::where('quote_id', $quote->id)->get();
    $acount_amount = (round($quote->amount * 30 / 100, 0) * 100);
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

    return view('client.quote-show', compact('step', 'quote', 'clientSecret', 'intent', 'customer', 'display_amount', 'options'));
  }

  public function createQuote($id)
  {
    $user = User::find($id);
    $projets = Projet::where('user_id', '=', $user->id)->get();
    $quote = Quote::where('user_id', '=', $user->id)->first();

    return view('admin.quote.create', compact('user', 'projets', 'quote'));
  }

  public function storeQuote(Request $request)
  {

    $quote = new Quote;
    $user = User::find($request->userId);
    $quote->amount = $request->amount;
    $quote->user_id = $user->id;
    $quote->projet_id = $request->projetId;
    $projet = Projet::where('user_id', '=', $user->id)->orderBy('created_at', 'desc')->first();

    if ($projet->as_quote === 0) {
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

        $filenametostore = $filename . '_' . time() . '.' . $extension;


        $filename = $files->storeAs(
          'documents',
          $filenametostore
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

      if ($quote->save()) {
        if ($request->option1[0] != null) {
          $option = new Option;
          $option->amount = $request->option1[0];
          $option->description = $request->option1[1];
          $option->quote_id = $quote->id;
          $option->save();
        }
        if ($request->option2[0] != null) {
          $option = new Option;
          $option->amount = $request->option2[0];
          $option->description = $request->option2[1];
          $option->quote_id = $quote->id;
          $option->save();
        }

        if ($request->option3[0] != null) {
          $option = new Option;
          $option->amount = $request->option3[0];
          $option->description = $request->option3[1];
          $option->quote_id = $quote->id;
          $option->save();
        }
      }


      // Notification
      $this->dispatch(new NewQuoteCreate($user));

      return view('admin.clients.show', compact('user'));
    }
    return redirect()->back()->withErrors('Devis déjà présent');
  }

  public function downloadQuote($quote)
  {

    $dl = Quote::find($quote);
    return Storage::download($dl->url);
  }

  public function validationQuote(Request $request)
  {
    if ($request->payment_method === 'stripe') {
      $quote = Quote::find($request->quote);
      return view('client.stripe', compact('quote'));
    }
  }

  public function acceptedQuote($quote)
  {

    $quote = Quote::find($quote);
    $user = User::where('id', '=', $quote->user_id);
    dd($user);
    if ($quote->accepted === 1) {
      $quote->accepted = 0;
    } elseif ($quote->accepted === 0) {
      $quote->accepted = 1;
    }

    $quote->save();
    return redirect()->back();
  }

  public function deleteQuote($quote_id, $projet_id)
  {
    $quote = Quote::find($quote_id);
    $file = File::where('filename', '=', $quote->filename)->first();
    $projet = Projet::find($projet_id);
    $user = User::find($quote->user_id);
    $options = Option::where('quote_id', $quote->id);

    if ($quote->user_id === Auth::user()->id || Auth::user()->role === 'admin') {
      Storage::disk('s3')->delete($quote->url);
      $projet->as_quote = 0;
      $user->step = 2;
      $user->save();
      $projet->save();
      $file->delete();
      $options->delete();
      $quote->delete();
    }

    return redirect()->back();
  }
}
