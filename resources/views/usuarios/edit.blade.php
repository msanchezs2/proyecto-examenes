@extends('layouts.simple')

@section('titulo', 'Editar Usuario')

@section('content')
    <div class="cabecera">
        <div>
            <h1>Editar usuario</h1>
            <p class="subtitle">{{ $usuario->name }} · {{ $usuario->email }}</p>
        </div>
    </div>

    @include('usuarios._form')
@endsection
