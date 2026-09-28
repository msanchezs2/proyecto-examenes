@extends('layouts.simple')

@section('titulo', 'Calificar Respuestas')

@section('content')
    <div class="cabecera">
        <div>
            <h1>Calificar respuestas abiertas</h1>
            <p class="subtitle">
                {{ $grupos->flatten(1)->count() }} respuesta(s) pendiente(s) en {{ $grupos->count() }} intento(s).
            </p>
        </div>
    </div>

    @forelse ($grupos as $respuestas)
        @php($intento = $respuestas->first()->intento)
        <article class="card">
            <div class="meta">
                <span class="badge badge-tema">{{ $intento->examen->titulo }}</span>
                <span class="dato">{{ $intento->alumno->name }}</span>
                <span class="dato">Intento #{{ $intento->id }}</span>
            </div>

            @foreach ($respuestas as $respuesta)
                <div class="respuesta-item">
                    <p class="enunciado" style="font-weight:600;">{{ $respuesta->pregunta->enunciado }}</p>
                    <p class="respuesta-texto">{{ $respuesta->texto ?: '(el alumno no escribió nada)' }}</p>

                    <form method="POST" action="{{ route('calificaciones.update', $respuesta) }}" class="form-calificar">
                        @csrf
                        @method('PUT')
                        <label>
                            Puntaje
                            <input
                                type="number"
                                name="puntaje"
                                step="0.5"
                                min="0"
                                max="{{ $respuesta->pregunta->puntaje }}"
                                placeholder="0 – {{ $respuesta->pregunta->puntaje }}"
                                required
                            >
                        </label>
                        <button type="submit" class="boton">Calificar</button>
                    </form>
                </div>
            @endforeach
        </article>
    @empty
        <p class="vacio">No hay respuestas pendientes de calificación.</p>
    @endforelse
@endsection
