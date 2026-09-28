@extends('layouts.simple')

@section('titulo', 'Nuevo Usuario')

@section('content')
    <div class="cabecera">
        <div>
            <h1>Nuevo usuario</h1>
            <p class="subtitle">Alta de un alumno o un administrador.</p>
        </div>
    </div>

    @include('usuarios._form')
@endsection
