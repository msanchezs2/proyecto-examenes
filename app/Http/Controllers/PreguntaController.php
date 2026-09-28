<?php

namespace App\Http\Controllers;

use App\Enums\TipoPregunta;
use App\Http\Requests\PreguntaRequest;
use App\Models\Pregunta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PreguntaController extends Controller
{
    public function index(Request $request): View
    {
        $tema = $request->string('tema')->toString();
        $tipo = $request->string('tipo')->toString();

        $preguntas = Pregunta::query()
            ->when($tema !== '', fn ($query) => $query->where('tema', $tema))
            ->when($tipo !== '', fn ($query) => $query->where('tipo', $tipo))
            ->orderBy('tema')
            ->orderBy('id')
            ->get();

        return view('preguntas.index', [
            'preguntas' => $preguntas,
            'temas' => $this->temasExistentes(),
            'temaSeleccionado' => $tema,
            'tipoSeleccionado' => $tipo,
        ]);
    }

    public function create(): View
    {
        return view('preguntas.create', [
            'temas' => $this->temasExistentes(),
        ]);
    }

    public function store(PreguntaRequest $request): RedirectResponse
    {
        $pregunta = Pregunta::create([
            ...$request->safe()->except(['opciones', 'correcta']),
            'creado_por' => $request->user()->id,
        ]);

        $this->guardarOpciones($pregunta, $request);

        return redirect()->route('preguntas.index')->with('status', 'Pregunta creada.');
    }

    public function edit(Pregunta $pregunta): View
    {
        $pregunta->load('opciones');

        return view('preguntas.edit', [
            'pregunta' => $pregunta,
            'temas' => $this->temasExistentes(),
        ]);
    }

    public function update(PreguntaRequest $request, Pregunta $pregunta): RedirectResponse
    {
        $pregunta->update($request->safe()->except(['opciones', 'correcta']));

        $this->guardarOpciones($pregunta, $request);

        return redirect()->route('preguntas.index')->with('status', 'Pregunta actualizada.');
    }

    /**
     * Actualiza las opciones por id cuando viene uno (updateOrCreate), en vez de
     * borrar y recrear todo: así una Respuesta existente que apunta a opcion_id
     * no queda huérfana solo porque se editó el texto de una opción.
     */
    private function guardarOpciones(Pregunta $pregunta, PreguntaRequest $request): void
    {
        if ($pregunta->tipo !== TipoPregunta::OpcionMultiple) {
            $pregunta->opciones()->delete();

            return;
        }

        $correctaIndice = (string) $request->input('correcta');
        $idsConservados = [];

        foreach ($request->input('opciones', []) as $indice => $datos) {
            if (blank($datos['texto'] ?? null)) {
                continue;
            }

            $id = filled($datos['id'] ?? null) ? (int) $datos['id'] : null;

            $opcion = $pregunta->opciones()->updateOrCreate(
                ['id' => $id],
                [
                    'texto' => $datos['texto'],
                    'es_correcta' => (string) $indice === $correctaIndice,
                ],
            );

            $idsConservados[] = $opcion->id;
        }

        $pregunta->opciones()->whereNotIn('id', $idsConservados)->delete();
    }

    private function temasExistentes(): Collection
    {
        return Pregunta::query()->select('tema')->distinct()->orderBy('tema')->pluck('tema');
    }
}
