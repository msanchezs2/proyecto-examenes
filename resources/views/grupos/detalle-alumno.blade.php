@extends('layouts.simple')

@section('titulo', 'Detalle del alumno')

@section('content')
    <div class="cabecera">
        <div>
            <a href="{{ route('grupos.examenes.calificaciones', [$grupo, $examen]) }}" class="dato">&larr; Calificaciones de {{ $examen->titulo }}</a>
            <h1>{{ $alumno->name }}</h1>
            <p class="subtitle">{{ $examen->titulo }} · {{ $grupo->nombre }} · {{ $puntajeTotal }} punto(s) en total.</p>
        </div>
    </div>

    @forelse ($intentos as $intento)
        <article class="card">
            <div class="meta">
                <span class="badge badge-tema">Intento {{ $loop->iteration }}</span>
                @switch($intento->estado)
                    @case(\App\Enums\EstadoIntento::Calificado)
                        <span class="badge badge-opcion_multiple">{{ $intento->calificacion_final }} / {{ $puntajeTotal }}</span>
                        @break
                    @case(\App\Enums\EstadoIntento::CalificacionParcial)
                        <span class="badge badge-abierta">Parcial: {{ $intento->calificacion_parcial }} pts</span>
                        @break
                    @case(\App\Enums\EstadoIntento::Entregado)
                        <span class="badge badge-abierta">Esperando corrección</span>
                        @break
                    @default
                        <span class="badge badge-abierta">En curso</span>
                @endswitch
                @if ($intento->salidas_pestana > 0)
                    <span class="badge badge-abierta">Salió de la pestaña {{ $intento->salidas_pestana }} vez/veces</span>
                @endif
                <span class="dato">Inicio: {{ $intento->inicio?->format('d/m/Y H:i') ?? '—' }}</span>
                <span class="dato">Fin: {{ $intento->fin?->format('d/m/Y H:i') ?? '—' }}</span>
            </div>

            @forelse ($intento->respuestas as $respuesta)
                @php($pregunta = $respuesta->pregunta)
                <div class="respuesta-item">
                    <div class="meta">
                        <span class="badge badge-{{ $pregunta->tipo->value }}">{{ $pregunta->tipo === \App\Enums\TipoPregunta::Abierta ? 'Abierta' : 'Opción múltiple' }}</span>
                        @if ($respuesta->estado === \App\Enums\EstadoRespuesta::Pendiente)
                            <span class="badge badge-abierta">Pendiente de calificar</span>
                        @else
                            <span class="badge badge-neutro">{{ $respuesta->puntaje }} / {{ $pregunta->puntaje }} pts</span>
                        @endif
                    </div>
                    <p class="enunciado" style="font-weight:600;">{{ $pregunta->enunciado }}</p>

                    @if ($pregunta->tipo === \App\Enums\TipoPregunta::OpcionMultiple)
                        <ul class="opciones">
                            @foreach ($pregunta->opciones as $opcion)
                                <li @class(['correcta' => $opcion->es_correcta])>
                                    {{ $opcion->texto }}
                                    @if ($respuesta->opcion_id === $opcion->id)
                                        &larr; respuesta del alumno
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @unless ($respuesta->opcion_id)
                            <p class="vacio">(el alumno no eligió ninguna opción)</p>
                        @endunless
                    @else
                        <p class="respuesta-texto">{{ $respuesta->texto ?: '(el alumno no escribió nada)' }}</p>
                    @endif
                </div>
            @empty
                <p class="vacio">Este intento no tiene respuestas registradas.</p>
            @endforelse
        </article>
    @empty
        <p class="vacio">Este alumno no ha iniciado el examen.</p>
    @endforelse
@endsection
