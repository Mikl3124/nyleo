<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Model\File;
use App\Model\User;
use App\Model\Message;
use App\Mail\NewMessage;
use App\Jobs\NewMessageJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Notifications\MessageNotification;
use Illuminate\Notifications\DatabaseNotification;

class ConversationController extends Controller
{
    public function showMessage()
    {
        $messages = Message::where('to_id', Auth::user()->id)
            ->orwhere('from_id', Auth::user()->id)
            ->latest()
            ->get();
        $user = User::where('role', '=', 'admin')->first();
        $step = Auth::user()->step;
        return view('messagerie.show', compact('step', 'messages', 'user'));
    }

    public function storeMessage(Request $request)
    {

        $values = $request->all();
        $to = User::where('role', '=', 'admin')->first();
        if ($files = $request->file('file_message')) {
            $rules = [];
        } else {
            $rules = [
                'content' => 'required',
            ];
        }

        $validator = Validator::make($values, $rules, [
            'content.required' => 'Veuillez écrire votre message',
            'file-message' => 'sometimes|max:5000',
        ]);
        if ($validator->fails()) {

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
            $filenametostore = $filename . '_' . time() . '.' . $extension;

            $filename = $files->storeAs(
                'documents',
                $filenametostore
            );
            File::create([
                'user_id' => Auth::user()->id,
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
        Mail::to($to->email)
            ->send(new NewMessage($message->content, $message->from_id));

        // $this->dispatch(new NewMessageJob($to->id, $message->content, $message->from_id));



        return redirect()->route('message.show', Auth::user()->id);
    }


    public function download($message)
    {

        $dl = Message::find($message);
        return Storage::download('documents/' . $dl->file_message);
    }

    public function showMessageNotification(Message $message, DatabaseNotification $notification)
    {
        $message->read_at = date('Y-m-d H:i:s');
        $message->save();
        $notification->markAsRead();

        if (Auth::user()->role === 'admin') {
            $user = User::find($notification->data['senderId']);
            $messages = Message::where('to_id', '=', $user->id)
                ->orwhere('from_id', '=', $user->id)
                ->latest()
                ->get();

            return view('admin.messagerie.show', compact('user', 'messages'));
        }
        $messages = Message::where('to_id', Auth::user()->id)
            ->orwhere('from_id', Auth::user()->id)
            ->latest()
            ->get();
        $user = User::where('role', '=', 'admin')->first();
        $step = $user->step;
        return view('messagerie.show', compact('step', 'messages', 'user'));
    }
}
