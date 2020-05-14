@extends('layouts.app')
@section('content')
 
    <div class="container">
        @if(Auth::user()->firstname)
            <h1 class="text-center mt-5">
                Bienvenue {{Auth::user()->firstname}}
            </h1>
        @else
            <h1 class="text-center mt-5">
                Bienvenue sur la plateforme Nyleo
            </h1>
            

        @endif
    </div>

@endsection



