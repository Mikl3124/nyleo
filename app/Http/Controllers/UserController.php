<?php

namespace App\Http\Controllers;

use Validator;
use App\Model\User;
use App\Mail\TestMail;
use App\Jobs\JobTestMail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Jobs\MailWelcomeMessageToUser;
use Illuminate\Support\Facades\Redirect;


class UserController extends Controller
{
  public function store(Request $request)
    {
      $user = New User;
      $value = $request->all();

        $rules = [
            'email' => 'required'
        ];

        $validator = Validator::make($value, $rules,[

          ]);

          if($validator->fails()){
            return Redirect::back()
              ->withErrors($validator)
              ->withInput();
          } else{
            $user->email = $request['email'];
            $user->password = Hash::make('nyleo');
            $user->role = 'client';
            $user->save();
            //Send mail to new client
            flashy()->success('Client enrigistré avec succès');
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

        $validator = Validator::make($value, $rules,[
            'password.confirmed' => 'Les mots de passe ne sont pas identiques',

          ]);

          if($validator->fails()){
            flashy()->error( 'Il y a une erreur dans le mot de passe');
            return Redirect::back()
              ->withErrors($validator)
              ->withInput();
          } else{
            $user->password = Hash::make($request['password']);
            $user->custom_password = true;
            if($user->save()){
              flashy()->success('Mot de passe modifié avec succès !');
              return view('client.dashboard', compact('step'));
            }
            return Redirect::back();
          }
    }

    public function clientEdit($user)
    {
      $user = User::find($user);
      if( $user === Auth::user() || Auth::user()->role === 'admin' ) {
        $step = $user->step;
        return view('client.client-form', compact('user', 'step'));
      } else {
        flashy()->error('Vous ne pouvez pas accéder à cette section');
        return Redirect::back();
      }
    }

    public function testMail()
    {
      $user = Auth::user();
        //Mail::to($user->email)->send(new TestMail($user));
        $this->dispatch(new JobTestMail($user));
        flashy()->success('L\'email de test a été envoyé');
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

        $validator = Validator::make($value, $rules,[

          ]);

          if($validator->fails()){
            flashy()->error('Il y a une erreur dans le formulaire');
            return Redirect::back()
              ->withErrors($validator)
              ->withInput();
            } else{
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

              if($user->save()){
                if ($user->step < 1){
                  $user->step = 1;
                }
                $user->save();
                //Send mail to new client
                $step = Auth::user()->step;
                flashy()->success('Enregistrement effectué, étape validée');
                $this->dispatch(new MailWelcomeMessageToUser($user));
                return view('client.dashboard', compact('step'));
              };
            return Redirect::back();

          }

    }
}
