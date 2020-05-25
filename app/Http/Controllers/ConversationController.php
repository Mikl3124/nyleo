<?php

namespace App\Http\Controllers;

use Validator;
use App\Model\User;
use App\Model\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class ConversationController extends Controller
{
    public function showMessage(){
        $messages = Message::where('to_id', Auth::user()->id)
                                    ->orwhere('from_id', Auth::user()->id)
                                    ->get();
        $user = Auth::user();
        $step = $user->step;
        return view('messagerie.show', compact('step', 'messages'));
    }

    public function storeMessage(Request $request){
        $values = $request->all();
        $to = User::where('role', '=', 'admin')->first();
        $rules = [
                'content' => 'required',
            ];

        $validator = Validator::make($values, $rules,[
            'content.required' => 'Veuillez écrire votre message',
            'file-message' => 'sometimes|max:5000',
          ]);
        if($validator->fails()){

        return Redirect::back()
            ->withErrors($validator)
            ->withInput();
        }

        $message = new Message;
                    $message->content = $request->content;
                    $message->from_id = Auth::user()->id;
                    $message->to_id = $to->id;

        $message->save();

        return redirect()->route('message.show');
    }
}
