@extends('layouts.simple')

@section('titulo', 'Exámenes del grupo')

@section('content')
    <div class="cabecera">
        <div>
            <a href="{{ route('grupos.index') }}" class="dato">&larr; Grupos</a>
            <h1>{{ $grupo->nombre }}</h1>
            <p class="subtitle">Exámenes aplicados a este grupo.</p>
        </div>
    </div>

    @forelse ($examenes as $examen)
        <article class="card">
            <div class="meta">
                <span class="dato">{{ $examen->apertura->format('d/m/Y H:i') }} &rarr; {{ $examen->cierre->format('d/m/Y H:i') }}</span>
                <span class="badge badge-opcion_multiple">{{ $examen->preguntas_count }} pregunta(s)</span>
            </div>
            <p class="enunciado" style="font-weight:600; font-size:16px;">{{ $examen->titulo }}</p>
            <div class="acciones">
                <a class="boton" href="{{ route('grupos.examenes.calificaciones', [$grupo, $examen]) }}">Ver calificaciones</a>
            </div>
        </article>
    @empty
        <p class="vacio">Todavía no hay exámenes aplicados a este grupo.</p>
    @endforelse
@endsection
