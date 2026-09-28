<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExamenRequest;
use App\Models\Examen;
use App\Models\Grupo;
use App\Models\Pregunta;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExamenController extends Controller
{
    public function index(): View
    {
        $examenes = Examen::withCount(['preguntas', 'grupos'])
            ->orderByDesc('apertura')
            ->get();

        return view('examenes.index', compact('examenes'));
    }

    public function create(): View
    {
        return view('examenes.create', [
            'grupos' => Grupo::orderBy('nombre')->get(),
            'preguntas' => Pregunta::orderBy('tema')->orderBy('id')->get(),
        ]);
    }

    public function store(ExamenRequest $request): RedirectResponse
    {
        $examen = Examen::create([
            ...$request->safe()->except(['grupos', 'preguntas']),
            'creado_por' => $request->user()->id,
        ]);

        $examen->grupos()->sync($request->input('grupos', []));
        $this->sincronizarPreguntas($examen, $request->input('preguntas', []));

        return redirect()->route('examenes.index')->with('status', 'Examen creado.');
    }

    public function edit(Examen $examen): View
    {
        $examen->load('grupos', 'preguntas');

        return view('examenes.edit', [
            'examen' => $examen,
            'grupos' => Grupo::orderBy('nombre')->get(),
            'preguntas' => Pregunta::orderBy('tema')->orderBy('id')->get(),
        ]);
    }

    public function update(ExamenRequest $request, Examen $examen): RedirectResponse
    {
        $examen->update($request->safe()->except(['grupos', 'preguntas']));
        $examen->grupos()->sync($request->input('grupos', []));
        $this->sincronizarPreguntas($examen, $request->input('preguntas', []));

        return redirect()->route('examenes.index')->with('status', 'Examen actualizado.');
    }

    public function destroy(Examen $examen): RedirectResponse
    {
        if ($examen->intentos()->exists()) {
            return back()->with('error', 'No se puede eliminar: el examen ya tiene intentos de alumnos registrados.');
        }

        $examen->delete();

        return redirect()->route('examenes.index')->with('status', 'Examen eliminado.');
    }

    /**
     * El orden queda según la posición en que llegan los checkboxes marcados.
     * El puntaje del pivot se deja null a propósito: usa el de Pregunta salvo
     * que en el futuro se agregue un override explícito por examen.
     */
    private function sincronizarPreguntas(Examen $examen, array $preguntaIds): void
    {
        $datos = [];
        foreach (array_values($preguntaIds) as $indice => $id) {
            $datos[$id] = ['orden' => $indice + 1];
        }

        $examen->preguntas()->sync($datos);
    }
}
