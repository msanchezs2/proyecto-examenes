@extends('layouts.simple')

@section('titulo', 'Editar Pregunta')

@section('content')
    <div class="cabecera">
        <div>
            <h1>Editar pregunta</h1>
            <p class="subtitle">{{ $pregunta->tema }} · usada en {{ $pregunta->examenes()->count() }} examen(es)</p>
        </div>
    </div>

    @include('preguntas._form')
@endsection
