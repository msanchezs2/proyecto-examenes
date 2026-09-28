@extends('layouts.simple')

@section('titulo', 'Banco de Preguntas')

@section('content')
    <div class="cabecera">
        <div>
            <h1>Banco de Preguntas</h1>
            <p class="subtitle">Preguntas reutilizables del sistema de exámenes.</p>
        </div>
        <a class="boton" href="{{ route('preguntas.create') }}">+ Nueva pregunta</a>
    </div>

    <form method="GET" class="form-filtros">
        <select name="tema" data-auto-enviar>
            <option value="">Todos los temas</option>
            @foreach ($temas as $tema)
                <option value="{{ $tema }}" @selected($temaSeleccionado === $tema)>{{ $tema }}</option>
            @endforeach
        </select>
        <select name="tipo" data-auto-enviar>
            <option value="">Todos los tipos</option>
            <option value="abierta" @selected($tipoSeleccionado === 'abierta')>Abierta</option>
            <option value="opcion_multiple" @selected($tipoSeleccionado === 'opcion_multiple')>Opción múltiple</option>
        </select>
    </form>

    <p class="contador">{{ $preguntas->count() }} pregunta(s)</p>

    @forelse ($preguntas as $pregunta)
        <article class="card">
            <div class="meta">
                <span class="badge badge-tema">{{ $pregunta->tema }}</span>
                <span class="badge badge-{{ $pregunta->tipo->value }}">
                    {{ $pregunta->tipo === \App\Enums\TipoPregunta::Abierta ? 'Abierta' : 'Opción múltiple' }}
                </span>
                <span class="dato">{{ $pregunta->puntaje }} pts</span>
                <span class="dato">{{ $pregunta->tiempo_seg }}s</span>
            </div>
            <p class="enunciado">{{ $pregunta->enunciado }}</p>

            <a class="boton secundario" href="{{ route('preguntas.edit', $pregunta) }}">Editar</a>
        </article>
    @empty
        <p class="vacio">No hay preguntas para este filtro.</p>
    @endforelse
@endsection
