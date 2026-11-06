<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Notifications\PortalLink;
use App\Services\ClientTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('portal.login');
    }

    public function sendLink(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']], [], ['email' => 'adresse e-mail']);
        if ($client = Client::where('email', $data['email'])->first()) {
            $client->notify(new PortalLink);
        }

        // Même message dans tous les cas pour ne pas révéler les adresses connues.
        return back()->with('status', 'Si cette adresse est connue, vous allez recevoir un e-mail avec votre lien de connexion dans quelques minutes.');
    }

    public function magic(Request $request, Client $client)
    {
        Auth::guard('client')->login($client, remember: true);
        $request->session()->regenerate();
        if ($request->query(ClientTracking::COPY_PARAM) !== null) {
            // Lien ouvert depuis la copie de Mickaël : ses visites ne comptent pas comme celles du client.
            $request->session()->put(ClientTracking::STAFF_SESSION, true);
        } else {
            $request->session()->forget(ClientTracking::STAFF_SESSION);
            $client->forceFill(['last_login_at' => now()])->saveQuietly();
        }

        $to = $request->query('to');

        return redirect(is_string($to) && str_starts_with($to, '/espace') ? $to : route('portal.home'));
    }

    public function logout(Request $request)
    {
        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login')->with('status', 'Vous êtes déconnecté.');
    }
}
