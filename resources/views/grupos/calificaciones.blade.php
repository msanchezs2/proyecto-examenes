@extends('layouts.simple')

@section('titulo', 'Calificaciones del examen')

@section('content')
    <div class="cabecera">
        <div>
            <a href="{{ route('grupos.examenes', $grupo) }}" class="dato">&larr; Exámenes de {{ $grupo->nombre }}</a>
            <h1>{{ $examen->titulo }}</h1>
            <p class="subtitle">Calificaciones de {{ $grupo->nombre }} · {{ $puntajeTotal }} punto(s) en total.</p>
        </div>
    </div>

    @if ($filas->isEmpty())
        <p class="vacio">Este grupo no tiene alumnos.</p>
    @else
        <div class="tabla-wrap">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Alumno</th>
                        <th>Intentos usados</th>
                        <th>Calificación</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($filas as $fila)
                        <tr>
                            <td>{{ $fila->alumno->name }}</td>
                            <td>{{ $fila->intentosUsados }}</td>
                            <td>
                                @if ($fila->calificado)
                                    <span class="badge badge-opcion_multiple">{{ $fila->calificado->calificacion_final }} / {{ $puntajeTotal }}</span>
                                @elseif ($fila->enProceso)
                                    @switch($fila->enProceso->estado)
                                        @case(\App\Enums\EstadoIntento::EnCurso)
                                            <span class="badge badge-abierta">En curso</span>
                                            @break
                                        @case(\App\Enums\EstadoIntento::Entregado)
                                            <span class="badge badge-abierta">Esperando corrección</span>
                                            @break
                                        @case(\App\Enums\EstadoIntento::CalificacionParcial)
                                            <span class="badge badge-abierta">Parcial: {{ $fila->enProceso->calificacion_parcial }} pts</span>
                                            @break
                                    @endswitch
                                @else
                                    <span class="badge badge-neutro">0 / {{ $puntajeTotal }} — No presentado</span>
                                @endif
                            </td>
                            <td>
                                @if ($fila->intentosUsados > 0)
                                    <a class="boton" href="{{ route('grupos.examenes.alumnos.detalle', [$grupo, $examen, $fila->alumno]) }}">Ver detalle</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
