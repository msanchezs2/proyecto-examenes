@extends('layouts.simple')

@section('titulo', 'Nuevo Examen')

@section('content')
    <div class="cabecera">
        <div>
            <h1>Nuevo examen</h1>
            <p class="subtitle">Armalo con preguntas del banco reutilizable.</p>
        </div>
    </div>

    @include('examenes._form')
@endsection
