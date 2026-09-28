@extends('layouts.simple')

@section('titulo', 'Nuevo Grupo')

@section('content')
    <div class="cabecera">
        <div>
            <h1>Nuevo grupo</h1>
            <p class="subtitle">Definí el nombre, ciclo y qué alumnos lo integran.</p>
        </div>
    </div>

    @include('grupos._form')
@endsection
