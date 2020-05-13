@extends('layouts.app')

@section('content')
<div class="container">

    <div class="card-block">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card mt-2">
                    <div class="card-body">
                        <div class=text-center>
                            <p>Connectez-vous avec vos identifiants</p>
                        </div>
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                        @if(isset($projet))
                            <input type="hidden" name="projet" value="{{ $projet }}">
                        @endif
                            <div class="form-group">
                                <input placeholder="Adresse e-mail" id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <div class="input-group" id="show_hide_password_1">
                                    <input placeholder="Mot de passe" id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                                    <div class="input-group-append">
                                        <span class="input-group-text"><a href=""><i class="fa fa-eye-slash" aria-hidden="true"></i></a></span>
                                    </div>
                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                                    <label class="form-check-label" for="remember">
                                        Se souvenir de moi
                                    </label>
                                </div>
                            </div>

                            <div class="form-group row mb-0">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                                        Se connecter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <p class="text-center"><a href="{{ route('password.request') }}">J'ai perdu mon mot de passe </a></p>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $("#show_hide_password_1 a").on('click', function(event) {
            event.preventDefault();
            if($('#show_hide_password_1 input').attr("type") == "text"){
                $('#show_hide_password_1 input').attr('type', 'password');
                $('#show_hide_password_1 i').addClass( "fa-eye-slash" );
                $('#show_hide_password_1 i').removeClass( "fa-eye" );
            }else if($('#show_hide_password_1 input').attr("type") == "password"){
                $('#show_hide_password_1 input').attr('type', 'text');
                $('#show_hide_password_1 i').removeClass( "fa-eye-slash" );
                $('#show_hide_password_1 i').addClass( "fa-eye" );
            }
        });
    });
</script>

@endsection
