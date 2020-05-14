<?php

namespace App\Http\Controllers;

use Validator;
use App\Model\User;
use Illuminate\Http\Request;
use MercurySeries\Flashy\Flashy;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;


class UserController extends Controller
{
    public function create()
    {
        return view('user.create');
    }

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
            Flashy::error("Il y a une erreur dans la création du client");
            return Redirect::back()
              ->withErrors($validator)
              ->withInput();
          } else{
            $user->email = $request['email'];
            $user->password = Hash::make('nyleo');
            $user->role = 'client';
            $user->save();
            Flashy::success("Le client a été créé avec succès!");
            return Redirect::back();
          }
    }

      public function changePassword(Request $request)
    {
      $user = Auth::user();
      $value = $request->all();

        $rules = [
            'password' => ['confirmed'],
        ];

        $validator = Validator::make($value, $rules,[
            'password.confirmed' => 'Les mots de passes ne sont pas identiques',

          ]);

          if($validator->fails()){
            Flashy::error("Un problème est survenu...");
            return Redirect::back()
              ->withErrors($validator)
              ->withInput();
          } else{
            $user->password = Hash::make($request['password']);
            $user->custom_password = true;
            if($user->save()){
              Flashy::success("Mot de passe modifié");
              return Redirect::back();
            }
            Flashy::error("Un problème est survenu...");
            return Redirect::back();
          }
    }
}
