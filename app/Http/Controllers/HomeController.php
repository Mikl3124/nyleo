<?php

namespace App\Http\Controllers;

use App\Model\Town;
use App\Model\User;
use App\Model\Projet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        if (Auth::user()){
          $step = Auth::user()->step;
          $projet = Projet::where('user_id', '=', Auth::user()->id)->orderBy('created_at', 'desc')->first();
          $users = User::where('id', '!=', Auth::user()->id)->get();

          if (Auth::user()->role != 'admin') {
              if (Auth::user()->custom_password != true) {
                  return view('client.welcome-change-password');
              } else {
                  return view('client.dashboard', compact('step', 'projet'));
              }
        }
            return view('admin.dashboard', compact('users'));
        } else {
            $step = 0;
            return view('auth.login', compact('step', 'projet'));
        }



    }

    public function admin()
    {
        return view('admin.dashboard', compact('step'));
    }
}
