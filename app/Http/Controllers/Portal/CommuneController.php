<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Communes;
use Illuminate\Http\Request;

class CommuneController extends Controller
{
    public function __invoke(Request $request)
    {
        return response()->json(Communes::search((string) $request->query('q', '')));
    }
}
