<?php

namespace App\Http\Controllers;

use Validator;
use App\Model\File;
use App\Model\User;
use App\Model\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;

class ConversationController extends Controller
{
    public function showMessage(){
        $messages = Message::where('to_id', Auth::user()->id)
                                    ->orwhere('from_id', Auth::user()->id)
                                    ->latest()
                                    ->get();
        $user = User::where('role', '=', 'admin')->first();
        $step = $user->step;
        return view('messagerie.show', compact('step', 'messages', 'user'));
    }

    public function storeMessage(Request $request){
        $values = $request->all();
        $to = User::where('role', '=', 'admin')->first();
        if ($files = $request->file('file_message')) {
            $rules = [
            ];
        }else{
            $rules = [
                'content' => 'required',
            ];
        }

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

        if ($files = $request->file('file_message')) {

            $filenamewithextension = $request->file('file_message')->getClientOriginalName();

            //get filename without extension
            $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);

            //get file extension
            $extension = $request->file('file_message')->getClientOriginalExtension();

            //filename to store
            //$path = 'documents/' . $user->lastname. '_' . $user->firstname . '_' . time();
            $filenametostore = $filename.'_'.time().'.'.$extension;

           $filename = $files->storeAs(
                'documents', $filenametostore
            );
              File::create([
                'user_id' => Auth::user()->id,
                'url' => Storage::disk('s3')->url($filenametostore),
              ]);
            //Store $filenametostore in the database
            $message->file_message = $filenametostore;
            }
        $message->save();

        return redirect()->route('message.show', Auth::user()->id);
    }


    public function download($message)
    {

        $dl = Message::find($message);
        return Storage::download('documents/' . $dl->file_message);

    }
}
