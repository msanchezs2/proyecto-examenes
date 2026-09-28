@extends('layouts.simple')

@section('titulo', 'Grupos')

@section('content')
    <div class="cabecera">
        <div>
            <h1>Grupos</h1>
            <p class="subtitle">Grupos de alumnos administrados.</p>
        </div>
        <a class="boton" href="{{ route('grupos.create') }}">+ Nuevo grupo</a>
    </div>

    @forelse ($grupos as $grupo)
        <article class="card">
            <div class="meta">
                <span class="badge badge-tema">{{ $grupo->ciclo }}</span>
                <span class="dato">{{ $grupo->alumnos_count }} alumno(s)</span>
            </div>
            <p class="enunciado" style="font-weight:600; font-size:16px;">{{ $grupo->nombre }}</p>
            <div class="acciones">
                <a class="boton secundario" href="{{ route('grupos.examenes', $grupo) }}">Ver exámenes</a>
                <a class="boton secundario" href="{{ route('grupos.edit', $grupo) }}">Editar</a>
                <form method="POST" action="{{ route('grupos.destroy', $grupo) }}" data-confirmar="¿Eliminar este grupo? Los alumnos y exámenes no se borran, solo se desvincula.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="boton secundario">Eliminar</button>
                </form>
            </div>
        </article>
    @empty
        <p class="vacio">No hay grupos todavía.</p>
    @endforelse
@endsection
