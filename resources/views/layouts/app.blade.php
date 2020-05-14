<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.js"></script>
    <script src="{{ asset('js/app.js') }}" defer></script>
    <!-- Mapbox  -->
    <script src='https://api.mapbox.com/mapbox-gl-js/v1.8.1/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v1.8.1/mapbox-gl.css' rel='stylesheet' />
    <script src='https://unpkg.com/es6-promise@4.2.4/dist/es6-promise.auto.min.js'></script>
    <script src="https://unpkg.com/@mapbox/mapbox-sdk/umd/mapbox-sdk.min.js"></script>



    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav mr-auto">

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Authentication Links -->
                        @guest
                        @else
                            {{Auth::user()->email}}
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }} <span class="caret"></span>
                                </a>

                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>
        <div class="container-fluid">
    {{-- ------------------------- steps ----------------------- --}}
    <div class="row d-flex justify-content-around mt-2">
            <div class="col-md2">
                @if ($step >= 0)
                    <a href="http://" class="btn btn-primary">Informations</a>
                @else
                    <a href="{{route('client.edit', Auth::user())}}" class="btn btn-primary">Etape 1 : Informations</a>
                @endif
            </div>
            <div class="col-md2">
                @if ($step >= 1)
                    <a href="http://" class="btn btn-primary">Projet</a>
                @else
                    <a href="http://" class="btn btn-secondary disabled">Etape 2: Projet</a>
                @endif
            </div>
            <div class="col-md2">
                @if ($step >= 2)
                    <a href="http://" class="btn btn-primary">Devis</a>
                @else
                    <a href="http://" class="btn btn-secondary disabled">Etape 3: Devis</a>
                @endif
            </div>
            <div class="col-md2">
                @if ($step >= 3)
                    <a href="http://" class="btn btn-primary">Avant-Projet</a>
                @else
                    <a href="http://" class="btn btn-secondary disabled">Etape 4: Avant-Projet</a>
                @endif
            </div>
            <div class="col-md2">
                @if ($step >= 4)
                    <a href="http://" class="btn btn-primary">Facture</a>
                @else
                    <a href="http://" class="btn btn-secondary disabled">Etape 5: Facture</a>
                @endif
            </div>
            <div class="col-md2">
                @if ($step >= 5)
                    <a href="http://" class="btn btn-primary">Consulter mon Projet</a>
                @else
                    <a href="http://" class="btn btn-secondary disabled">Etape 6: Livraison</a>
                @endif
            </div>
    </div>
        <main class="py-4">
            @yield('content')
        </main>
    </div>
    @include('flashy::message')

</body>
</html>
