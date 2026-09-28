@extends('layouts.simple')

@section('titulo', 'Editar Grupo')

@section('content')
    <div class="cabecera">
        <div>
            <h1>Editar grupo</h1>
            <p class="subtitle">{{ $grupo->nombre }} · {{ $grupo->ciclo }}</p>
        </div>
    </div>

    @include('grupos._form')
@endsection
