@extends('layouts.auth')

@section('titulo', 'Iniciar sesión')

@section('content')
    <h1 class="auth-titulo">Iniciar sesión</h1>
    <p class="auth-subtitulo">Entrá con tu correo y contraseña.</p>

    @if (session('status'))
        <div class="aviso">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="errores">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf

        <label>
            Correo
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        </label>

        <label>
            Contraseña
            <input type="password" name="password" required autocomplete="current-password">
        </label>

        <div class="auth-fila">
            <label class="auth-check">
                <input type="checkbox" name="recordar" value="1">
                Recordarme
            </label>
            <a href="{{ route('password.request') }}" class="auth-link">¿Olvidaste tu contraseña?</a>
        </div>

        <button type="submit" class="boton auth-submit">Entrar</button>
    </form>
@endsection
