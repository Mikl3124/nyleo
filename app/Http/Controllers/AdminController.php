<?php

namespace App\Http\Controllers;

use Validator;
use App\Model\File;
use App\Model\User;
use App\Model\Quote;
use App\Model\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use App\Notifications\MessageNotification;

class AdminController extends Controller
{
    public function showClient($user)
    {
      $user = User::find($user);
      return view('admin.clients.show', compact('user'));
    }

    public function showMessage($user)
    {
      $user = User::find($user);
      $messages = Message::where('to_id', '=', $user->id)
                          ->orwhere('from_id', '=', $user->id)
                          ->latest()
                          ->get();

      return view('admin.messagerie.show', compact('user', 'messages'));
    }

    public function storeMessage(Request $request){

        $values = $request->all();
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
                    $message->to_id = $request->to;

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
                'user_id' => $request->to,
                'url' => Storage::disk('s3')->url('documents/' . $filenametostore),
                'filename' => $filenamewithextension
              ]);
            //Store $filenametostore in the database
            $message->file_message = $filename;
            $message->filename = $filenamewithextension;
            }
        $message->save();

        // Notification

        $message->to->notify(new MessageNotification($message, auth()->user()));

        return redirect()->route('admin.message.show', $request->to);
    }

    public function download($message)
    {

        $dl = Message::find($message);
        return Storage::download($dl->file_message);

    }

    public function showDocuments($id)
    {
      $user = User::find($id);
      $documents = File::where('user_id', '=', $user->id)->get();

      return view('admin.documents.show', compact('documents'));
    }

    public function showUploadPage($id)
    {
      $user = User::find($id);
      $step = $user->step;
      return view('admin.upload-file', compact('user', 'step'));
    }

    public function connectAsClient($id)
    {
      $user = User::find($id);
      Auth::login($user);
      return redirect()->route('home');
    }

}
