<?php

namespace App\Http\Controllers;

use App\Model\File;
use App\Model\Projet;
use App\Model\FileEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProjetController extends Controller
{
    public function projetEdit()
    {
      $user = Auth::user();
      $projet = new Projet;
      $step = $user->step;
      return view('client.projet-form', compact('user', 'step', 'projet'));
    }

    public function showUploadPage()
    {
      $user = Auth::user();
      $step = $user->step;
      return view('client.upload-file', compact('user', 'step'));
    }


    public function uploadFile(Request $request)
    {
      $files = $request->file5;
      $results = array_pop($files);
      $user = Auth::user();
        foreach ($files as $file) {
            $filename = $file->store('files');
            $test = File::create([
                'user_id' => $user->id,
                'url' => Storage::disk('s3')->url($filename),
            
            ]);
            dd($test);
        }
        return 'Upload successful!';
    }
}
