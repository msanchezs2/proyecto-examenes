@extends('layouts.simple')

@section('titulo', 'Nueva Pregunta')

@section('content')
    <div class="cabecera">
        <div>
            <h1>Nueva pregunta</h1>
            <p class="subtitle">Se agrega al banco reutilizable de preguntas.</p>
        </div>
    </div>

    @include('preguntas._form')
@endsection
