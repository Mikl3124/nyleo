<?php

namespace App\Http\Controllers;

use Validator;
use App\Model\File;
use App\Model\User;
use App\Model\Quote;
use App\Model\Projet;
use App\Model\Paiement;
use App\Model\FileEntry;
use App\Model\Avantprojet;
use Illuminate\Http\Request;
use App\Mail\SendAvantprojet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;

class ProjetController extends Controller
{
  public function projetShow($id)
  {
    $projet = Projet::where('user_id', '=', $id)->first();
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

    $validator = Validator::make($value, $rules, []);

    if ($validator->fails()) {
      return Redirect::back()
        ->withErrors($validator)
        ->withInput();
    } else {
      $projet->user_id = Auth::user()->id;
      $projet->description = $request->description;
      $projet->address = $request->address;
      $projet->cp = $request->cp;
      $projet->town = $request->town;
      $projet->section = $request->section;
      $projet->number = $request->number;
      $projet->superficie = $request->superficie;
      if ($request->multiple_parcelles === 'on') {
        $projet->multiple_parcelles = true;
      }

      if ($projet->save()) {
        if ($user->step < 2) {
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

    $validator = Validator::make($value, $rules, []);

    if ($validator->fails()) {
      return Redirect::back()
        ->withErrors($validator)
        ->withInput();
    } else {
      $projet = new Projet;
      $projet->user_id = Auth::user()->id;
      $projet->description = $request->description;
      $projet->address = $request->address;
      $projet->cp = $request->cp;
      $projet->town = $request->town;
      $projet->section = $request->section;
      $projet->number = $request->number;
      $projet->superficie = $request->superficie;
      if ($request->multiple_parcelles === 'on') {
        $projet->multiple_parcelles = true;
      }

      if ($projet->save()) {
        if ($user->step < 2) {
          $user->step = 2;
          $user->save();
        }
        $projet->save();
      };
      return view('client.upload-file', compact('step', 'user'));
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
    $step = Auth::user()->step;
    $user = User::find($request->userId);
    if ($files = $request->file('file5')) {
      $files = $request->file5;
      $results = array_pop($files);
      foreach ($files as $file) {
        $filenamewithextension = $file->getClientOriginalName();

        //get filename without extension
        $originalfilename = pathinfo($filenamewithextension, PATHINFO_FILENAME);

        //get file extension
        $extension = $file->getClientOriginalExtension();

        //filename to store
        $filenametostore = $originalfilename . '_' . time() . '.' . $extension;

        $filename = $file->storeAs(
          'documents',
          $filenametostore
        );
        File::create([
          'user_id' => $user->id,
          'url' => Storage::disk('s3')->url('documents/' . $filenametostore),
          'filename' => $filenamewithextension
        ]);
      }
      return redirect()->route('documents.show', $user->id)->with('success', 'Vos documents ont bien été enregistrés');
    }
    return redirect()->route('documents.show', $user->id)->with('error', 'Aucun document envoyé');
  }

  public function showDocuments($id)
  {
    $user = User::find($id);
    $step = $user->step;
    $documents = File::where('user_id', '=', $user->id)->get();

    return view('client.documents-show', compact('step', 'documents'));
  }

  public function avantProjetCreate($id)
  {
    $user = User::find($id);
    $projet = Projet::where('user_id', '=', $user->id)->first();
    $avant_projet = Avantprojet::where('projet_id', '=', $projet->id)->first();
    return view('admin.avant-projet.create', compact('user', 'projet', 'avant_projet'));
  }

  public function avantProjetStore(Request $request)
  {
    $avant_projet = new Avantprojet;
    $avant_projet->projet_id = $request->projetId;
    $avant_projet->url = $request->url;
    $user = User::find($request->userId);
    $projet = Projet::find($request->projetId);
    $projet->as_avantProjet = 1;
    $projet->save();

    if ($avant_projet->save()) {
      if ($user->step < 3) {
        $user->step = 3;
        $user->save();
      }
      Mail::to($user->email)
        ->send(new SendAvantprojet($user));
      return redirect()->route('admin.client.show', $user->id)->with('success', "L'avant projet a bien été envoyé");
    }

    return redirect()->route('admin.client.show', $user->id)->with('error', "Un problème est survenu");
  }

  public function deleteDocument($id)
  {
    $file = File::find($id);
    if ($file->user_id === Auth::user()->id || Auth::user()->role === 'admin') {
      $file->delete();
    }
    return redirect()->back();
  }

  public function avantprojetShow($id)
  {
    $user = User::find($id);
    $projet = Projet::where('user_id', '=', $id)->first();
    $avantprojet = Avantprojet::where('projet_id', '=', $projet->id)->first();
    $quote = Quote::where('projet_id', $projet->id)->first();
    $step = Auth::user()->step;
    $paiement = Paiement::where('quote_id', $quote->id)->where('user_id', $user->id)->first();
    return view('client.avantprojet-show', compact('step', 'avantprojet', 'quote', 'paiement'));
  }

  public function deleteAvantProjet($projet_id)
  {

    $avant_projet = Avantprojet::where('projet_id', '=', $projet_id)->first();
    $projet = Projet::where('id', '=', $projet_id)->first();
    $user = User::find($projet->user_id);
    if (Auth::user()->role === 'admin') {
      $projet->as_avantProjet = 0;
      $projet->save();
      $user->step = 3;
      $user->save();
      $avant_projet->delete();
    }

    return redirect()->back();
  }
}
