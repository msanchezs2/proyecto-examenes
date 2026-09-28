@extends('layouts.simple')

@section('titulo', 'Exámenes')

@section('content')
    <div class="cabecera">
        <div>
            <h1>Exámenes</h1>
            <p class="subtitle">Exámenes armados a partir del banco de preguntas.</p>
        </div>
        <a class="boton" href="{{ route('examenes.create') }}">+ Nuevo examen</a>
    </div>

    @forelse ($examenes as $examen)
        <article class="card">
            <div class="meta">
                <span class="dato">{{ $examen->apertura->format('d/m/Y H:i') }} &rarr; {{ $examen->cierre->format('d/m/Y H:i') }}</span>
                <span class="dato">{{ $examen->duracion_min }} min</span>
                <span class="dato">{{ $examen->max_intentos }} intento(s)</span>
            </div>
            <p class="enunciado" style="font-weight:600; font-size:16px;">{{ $examen->titulo }}</p>
            <div class="meta">
                <span class="badge badge-tema">{{ $examen->grupos_count }} grupo(s)</span>
                <span class="badge badge-opcion_multiple">{{ $examen->preguntas_count }} pregunta(s)</span>
            </div>
            <div class="acciones">
                <a class="boton secundario" href="{{ route('examenes.edit', $examen) }}">Editar</a>
                <form method="POST" action="{{ route('examenes.destroy', $examen) }}" data-confirmar="¿Eliminar este examen?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="boton secundario">Eliminar</button>
                </form>
            </div>
        </article>
    @empty
        <p class="vacio">No hay exámenes todavía.</p>
    @endforelse
@endsection
