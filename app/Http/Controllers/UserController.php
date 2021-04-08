<?php

namespace App\Http\Controllers;

use App\Model\User;
use App\Mail\TestMail;
use App\Jobs\JobTestMail;
use Illuminate\Http\Request;
use App\Jobs\JobStep1ToAdmin;
use App\Jobs\MailStep1ToAdmin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Validator;
use App\Jobs\MailWelcomeMessageToUser;
use Illuminate\Support\Facades\Redirect;


class UserController extends Controller
{
  public function clientShow()
  {
    $step = Auth::user()->step;
    return view('client.client-show', compact('step'));
  }

  public function store(Request $request)
  {
    $user = new User;
    $value = $request->all();

    $rules = [
      'email' => 'required'
    ];

    $validator = Validator::make($value, $rules, []);

    if ($validator->fails()) {
      return Redirect::back()
        ->withErrors($validator)
        ->withInput();
    } else {
      $user->email = $request['email'];
      $user->password = Hash::make('nyleo');
      $user->role = 'client';
      $user->save();
      //Send mail to new client
      // $this->dispatch(new MailWelcomeMessageToUser($user));
      return Redirect::back();
    }
  }

  public function storeSimple(Request $request)
  {
    $user = new User;
    $value = $request->all();

    $rules = [
      'email' => 'required',
      'lastname' => 'required',
      'firstname' => 'required',
    ];


    $validator = Validator::make($value, $rules, []);

    if ($validator->fails()) {
      return Redirect::back()
        ->withErrors($validator)
        ->withInput();
    } else {
      $user->email = $request['email'];
      $user->password = Hash::make('nyleo');
      $user->lastname = $request->lastname;
      $user->firstname = $request->firstname;
      $user->role = 'client';

      $user->save();
      //Send mail to new client
      $this->dispatch(new MailWelcomeMessageToUser($user));
      return Redirect::back();
    }
  }

  public function changePassword(Request $request)
  {
    $user = Auth::user();
    $step = 0;
    $value = $request->all();

    $rules = [
      'password' => ['confirmed'],
    ];

    $validator = Validator::make($value, $rules, [
      'password.confirmed' => 'Les mots de passe ne sont pas identiques',

    ]);

    if ($validator->fails()) {
      return Redirect::back()
        ->withErrors($validator)
        ->withInput();
    } else {
      $user->password = Hash::make($request['password']);
      $user->custom_password = true;
      if ($user->save()) {
        return view('client.dashboard', compact('step'));
      }
      return Redirect::back();
    }
  }

  public function clientEdit()
  {
    $user = Auth::user();
    $step = $user->step;
    return view('client.client-form', compact('user', 'step'));
  }

  public function testMail()
  {
    $user = Auth::user();
    //Mail::to($user->email)->send(new TestMail($user));
    $this->dispatch(new JobTestMail($user));
    return Redirect::back();
  }


  public function clientUpdate(Request $request, $user)
  {

    $user = User::find($user);
    $value = $request->all();
    $rules = [
      'lastname' => 'required',
      'firstname' => 'required',
      'birth' => 'required',
      'birthplace' => 'required',
      'address' => 'required',
      'town' => 'required',
      'cp' => 'required | numeric',
      'email' => 'required | email',
      'phone' => 'required'
    ];

    $validator = Validator::make($value, $rules, []);

    if ($validator->fails()) {
      return Redirect::back()
        ->withErrors($validator)
        ->withInput();
    } else {
      $user->email = $request['email'];
      $user->lastname = $request->lastname;
      $user->firstname = $request->firstname;
      $user->birth = $request->birth;
      $user->birthplace = $request->birthplace;
      $user->address = $request->address;
      $user->town = $request->town;
      $user->cp = $request->cp;
      $user->email = $request->email;
      $user->phone = $request->phone;

      if ($user->save()) {

        if ($user->step < 1) {
          $user->step = 1;
        }
        $user->save();
        //Send mail to new client
        $step = Auth::user()->step;
        $this->dispatch(new JobStep1ToAdmin($user));
        return view('client.dashboard', compact('step'));
      };
      return Redirect::back();
    }
  }
}
