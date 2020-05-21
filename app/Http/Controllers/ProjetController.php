<?php

namespace App\Http\Controllers;

use App\Model\File;
use App\Model\Projet;
use App\Model\FileEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjetController extends Controller
{
    public function projetEdit()
    {
      $user = Auth::user();
      $projet = new Projet;
      $step = $user->step;
      return view('client.projet-form', compact('user', 'step', 'projet'));
    }

    public function uploadFile(Request $request)
    {
      $files = $request->file5;
      $results = array_pop($files);

      $user = Auth::user();
        foreach ($files as $file) {
            $filename = $file->store('files');
            File::create([
                'user_id' => $user->id,
                'filename' => $filename,
                'url' => Storage::disk('s3')->url($path)
            ]);
        }
        return 'Upload successful!';
    }
}
