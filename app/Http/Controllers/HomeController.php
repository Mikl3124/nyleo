<?php

namespace App\Http\Controllers;

use App\Model\Town;
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
          if (Auth::user()->role != 'admin') {
              if (Auth::user()->custom_password != true) {
                  flashy()->success('Bienvenue');
                  return view('client.welcome-change-password');
              } else {
                  flashy()->success('Bienvenue');
                  return view('client.dashboard', compact('step'));
              }
        }   flashy()->success('Bienvenue');
            return view('admin.dashboard');
        } else {
            $step = 0;
            return view('auth.login', compact('step'));
        }



    }

    public function admin()
    {

        return view('admin.dashboard', compact('step'));
    }
}
