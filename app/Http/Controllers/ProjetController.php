<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjetController extends Controller
{
    public function projetEdit()
    {
      $user = Auth::user();
      $step = $user->step;
      return view('client.projet-form', compact('user', 'step'));
    }
}
