<?php

namespace App\Http\Controllers;

use App\Enums\EstadoIntento;
use App\Http\Requests\GrupoRequest;
use App\Models\Alumno;
use App\Models\Examen;
use App\Models\Grupo;
use App\Models\IntentoExamen;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GrupoController extends Controller
{
    public function index(): View
    {
        $grupos = Grupo::withCount('alumnos')->orderBy('nombre')->get();

        return view('grupos.index', compact('grupos'));
    }

    public function examenes(Grupo $grupo): View
    {
        $examenes = $grupo->examenes()
            ->withCount('preguntas')
            ->orderByDesc('apertura')
            ->get();

        return view('grupos.examenes', compact('grupo', 'examenes'));
    }

    /**
     * Calificación de cada alumno del grupo en un examen puntual: el mejor
     * intento calificado, o 0/"no presentado" si nunca lo rindió. Los
     * intentos entregados pero todavía sin calificar (respuestas abiertas
     * pendientes) se muestran aparte, no se cuentan como 0.
     */
    public function calificaciones(Grupo $grupo, Examen $examen): View
    {
        abort_unless($examen->grupos()->where('grupos.id', $grupo->id)->exists(), 404);

        // sum('puntaje') a secas es ambiguo: examen_pregunta también tiene su
        // propia columna "puntaje" (el override que sincronizarPreguntas()
        // deja en null salvo que a futuro se use).
        $puntajeTotal = $examen->preguntas()->sum('preguntas.puntaje');

        $filas = $grupo->alumnos()
            ->orderBy('name')
            ->get()
            ->map(function (Alumno $alumno) use ($examen) {
                $intentos = $examen->intentos()->where('alumno_id', $alumno->id)->get();

                return (object) [
                    'alumno' => $alumno,
                    'intentosUsados' => $intentos->count(),
                    'calificado' => $intentos->where('estado', EstadoIntento::Calificado)->sortByDesc('calificacion_final')->first(),
                    'enProceso' => $intentos->first(fn ($intento) => in_array($intento->estado, [
                        EstadoIntento::EnCurso, EstadoIntento::Entregado, EstadoIntento::CalificacionParcial,
                    ], true)),
                ];
            });

        return view('grupos.calificaciones', compact('grupo', 'examen', 'filas', 'puntajeTotal'));
    }

    /**
     * Detalle de todos los intentos de un alumno del grupo en un examen:
     * qué respondió en cada pregunta y cuántos puntos obtuvo.
     */
    public function detalleAlumno(Grupo $grupo, Examen $examen, Alumno $alumno): View
    {
        abort_unless($examen->grupos()->where('grupos.id', $grupo->id)->exists(), 404);
        abort_unless($grupo->alumnos()->where('users.id', $alumno->id)->exists(), 404);

        $puntajeTotal = $examen->preguntas()->sum('preguntas.puntaje');

        $intentos = $examen->intentos()
            ->where('alumno_id', $alumno->id)
            ->with(['respuestas.pregunta.opciones', 'respuestas.opcion'])
            ->orderBy('id')
            ->get()
            ->each(function (IntentoExamen $intento) {
                $orden = array_flip($intento->orden_preguntas ?? []);

                $intento->setRelation(
                    'respuestas',
                    $intento->respuestas->sortBy(fn ($respuesta) => $orden[$respuesta->pregunta_id] ?? PHP_INT_MAX)->values()
                );
            });

        return view('grupos.detalle-alumno', compact('grupo', 'examen', 'alumno', 'intentos', 'puntajeTotal'));
    }

    public function create(): View
    {
        return view('grupos.create', [
            'alumnos' => Alumno::with('alumnoPerfil')->orderBy('name')->get(),
        ]);
    }

    public function store(GrupoRequest $request): RedirectResponse
    {
        $grupo = Grupo::create([
            ...$request->safe()->except('alumnos'),
            'administrado_por' => $request->user()->id,
        ]);

        $grupo->alumnos()->sync($request->input('alumnos', []));

        return redirect()->route('grupos.index')->with('status', 'Grupo creado.');
    }

    public function edit(Grupo $grupo): View
    {
        $grupo->load('alumnos');

        return view('grupos.edit', [
            'grupo' => $grupo,
            'alumnos' => Alumno::with('alumnoPerfil')->orderBy('name')->get(),
        ]);
    }

    public function update(GrupoRequest $request, Grupo $grupo): RedirectResponse
    {
        $grupo->update($request->safe()->except('alumnos'));
        $grupo->alumnos()->sync($request->input('alumnos', []));

        return redirect()->route('grupos.index')->with('status', 'Grupo actualizado.');
    }

    public function destroy(Grupo $grupo): RedirectResponse
    {
        $grupo->delete();

        return redirect()->route('grupos.index')->with('status', 'Grupo eliminado.');
    }
}
