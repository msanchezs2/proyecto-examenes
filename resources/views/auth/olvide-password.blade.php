@extends('layouts.auth')

@section('titulo', 'Recuperar contraseña')

@section('content')
    <h1 class="auth-titulo">Recuperar contraseña</h1>
    <p class="auth-subtitulo">Ingresá tu correo y te mandamos un enlace para elegir una nueva.</p>

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

    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
        @csrf

        <label>
            Correo
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        </label>

        <button type="submit" class="boton auth-submit">Enviar enlace</button>
    </form>

    <p class="auth-pie"><a href="{{ route('login') }}" class="auth-link">&larr; Volver a iniciar sesión</a></p>
@endsection
