@extends('layouts.auth')

@section('titulo', 'Restablecer contraseña')

@section('content')
    <h1 class="auth-titulo">Elegí tu nueva contraseña</h1>
    <p class="auth-subtitulo">{{ $email }}</p>

    @if ($errors->any())
        <div class="errores">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="auth-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <label>
            Nueva contraseña
            <input type="password" name="password" required autocomplete="new-password" minlength="10">
            <span class="ayuda">Mínimo 10 caracteres, con mayúsculas, minúsculas y números.</span>
        </label>
        <label>
            Confirmá la contraseña
            <input type="password" name="password_confirmation" required autocomplete="new-password" minlength="10">
        </label>

        <button type="submit" class="boton auth-submit">Guardar contraseña</button>
    </form>
@endsection
