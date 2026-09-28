@extends('layouts.simple')

@section('titulo', 'Editar Examen')

@section('content')
    <div class="cabecera">
        <div>
            <h1>Editar examen</h1>
            <p class="subtitle">{{ $examen->titulo }}</p>
        </div>
    </div>

    @include('examenes._form')
@endsection
