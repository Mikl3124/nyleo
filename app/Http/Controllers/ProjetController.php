<?php

namespace App\Http\Controllers;

use Validator;
use App\Model\File;
use App\Model\Projet;
use App\Model\FileEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;

class ProjetController extends Controller
{
    public function projetEdit()
    {
      $user = Auth::user();
      $projet = new Projet;
      $step = $user->step;
      return view('client.projet-form', compact('user', 'step', 'projet'));
    }

    public function projetCreate(Request $request)
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
      $files = $request->file5;
      $results = array_pop($files);
      $user = Auth::user();
        foreach ($files as $file) {
            // $filename = $file->store('documents');
            // File::create([
            //     'user_id' => $user->id,
            //     'url' => Storage::disk('s3')->url($filename),
            // ]);
            $filenamewithextension = $file->getClientOriginalName();

            //get filename without extension
            $originalfilename = pathinfo($filenamewithextension, PATHINFO_FILENAME);

            //get file extension
            $extension = $file->getClientOriginalExtension();

            //filename to store
            //$path = 'documents/' . $user->lastname. '_' . $user->firstname . '_' . time();
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

        return view('client.dashboard', compact('step'));
    }
}
