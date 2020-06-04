<?php

namespace App\Http\Controllers;

use Validator;
use App\Model\File;
use App\Model\User;
use App\Model\Projet;
use App\Model\FileEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;

class ProjetController extends Controller
{
    public function projetShow($id)
    {
      $projet = Projet::where('user_id', '=', $id )->first();
      $step = Auth::user()->step;
      return view('client.projet-show', compact('step', 'projet'));
    }

    public function projetCreate($id)
    {
      $user = User::find($id);
      $step = $user->step;
      return view('client.projet-form', compact('user', 'step'));
    }

    public function projetEdit($id)
    {
      $projet = Projet::find($id);
      $user = User::find($projet->user->id);
      $step = $user->step;
      return view('client.projet-edit', compact('user', 'step', 'projet'));
    }

    public function projetUpdate(Request $request)
    {
      $projet = Projet::find($request->projetId);
      $user = Auth::user();
      $step = $user->step;
      $value = $request->all();
      $rules = [
            'description' => 'required',
            'address' => 'required',
            'cp' => 'required',
            'town' => 'required',
        ];

        $validator = Validator::make($value, $rules,[

          ]);

          if($validator->fails()){
            return Redirect::back()
              ->withErrors($validator)
              ->withInput();
            } else{
              $projet->user_id = Auth::user()->id;
              $projet->description = $request->description;
              $projet->address = $request->address;
              $projet->cp = $request->cp;
              $projet->town = $request->town;
              $projet->section = $request->section;
              $projet->number = $request->number;
              $projet->superficie = $request->superficie;
              if($request->multiple_parcelles === 'on'){
                $projet->multiple_parcelles = true;
              }

              if($projet->save()){
                if ($user->step < 2){
                  $user->step = 2;
                  $user->save();
                }
                $projet->save();

              };
            return view('client.dashboard', compact('step'));

          }

    }

    public function projetStore(Request $request)
    {
      $user = Auth::user();
      $step = $user->step;
      $value = $request->all();
      $rules = [
            'description' => 'required',
            'address' => 'required',
            'cp' => 'required',
            'town' => 'required',
        ];

        $validator = Validator::make($value, $rules,[

          ]);

          if($validator->fails()){
            return Redirect::back()
              ->withErrors($validator)
              ->withInput();
            } else{
              $projet = New Projet;
              $projet->user_id = Auth::user()->id;
              $projet->description = $request->description;
              $projet->address = $request->address;
              $projet->cp = $request->cp;
              $projet->town = $request->town;
              $projet->section = $request->section;
              $projet->number = $request->number;
              $projet->superficie = $request->superficie;
              if($request->multiple_parcelles === 'on'){
                $projet->multiple_parcelles = true;
              }

              if($projet->save()){
                if ($user->step < 2){
                  $user->step = 2;
                  $user->save();
                }
                $projet->save();

              };
            return view('client.upload-file', compact('step'));

          }

    }


    public function showUploadPage()
    {
      $user = Auth::user();
      $step = $user->step;
      return view('client.upload-file', compact('user', 'step'));
    }


    public function uploadFile(Request $request)
    {
      $step =Auth::user()->step;
      if ($files = $request->file('file5')){
        $files = $request->file5;
      $results = array_pop($files);
      $user = Auth::user();
        foreach ($files as $file) {
            $filenamewithextension = $file->getClientOriginalName();

          //get filename without extension
          $originalfilename = pathinfo($filenamewithextension, PATHINFO_FILENAME);

          //get file extension
          $extension = $file->getClientOriginalExtension();

          //filename to store
          $filenametostore = $originalfilename .'_'.time().'.'.$extension;

          $filename = $file->storeAs(
              'documents', $filenametostore
            );
              File::create([
                'user_id' => $user->id,
                'url' => Storage::disk('s3')->url('documents/' . $filenametostore),
                'filename' => $filenamewithextension
              ]);
        }
      }

        return view('client.dashboard', compact('step'));
    }

    public function showDocuments()
    {
      $user = Auth::user();
      $step = $user->step;
      $documents = File::where('user_id', '=', $user->id)->get();

      return view('client.documents-show', compact('step', 'documents'));
    }
}
