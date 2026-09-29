<?php

namespace App\Http\Controllers;

use App\Enums\EstadoIntento;
use App\Enums\EstadoRespuesta;
use App\Enums\TipoPregunta;
use App\Models\Examen;
use App\Models\IntentoExamen;
use App\Models\Pregunta;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class IntentoExamenController extends Controller
{
    /** Salidas de pestaña toleradas antes de entregar el examen automáticamente. */
    public const MAX_SALIDAS_PESTANA = 3;

    public function index(Request $request): View
    {
        $usuario = $request->user();
        $grupoIds = $usuario->grupos()->pluck('grupos.id');

        $examenes = Examen::whereHas('grupos', fn ($query) => $query->whereIn('grupos.id', $grupoIds))
            ->where('cierre', '>=', now())
            ->withCount(['intentos as intentos_usados' => fn ($query) => $query->where('alumno_id', $usuario->id)])
            ->orderBy('apertura')
            ->get()
            ->each(function (Examen $examen) use ($usuario) {
                $examen->intentoEnCurso = $usuario->intentos()
                    ->where('examen_id', $examen->id)
                    ->where('estado', EstadoIntento::EnCurso)
                    ->first();
            });

        $intentos = $usuario->intentos()->with('examen')->orderByDesc('inicio')->get();

        $promedio = $usuario->intentos()->where('estado', EstadoIntento::Calificado)->avg('calificacion_final');
        $esperandoCorreccion = $usuario->intentos()
            ->whereIn('estado', [EstadoIntento::Entregado, EstadoIntento::CalificacionParcial])
            ->count();

        return view('intentos.index', [
            'examenes' => $examenes,
            'intentos' => $intentos,
            'promedio' => $promedio,
            'esperandoCorreccion' => $esperandoCorreccion,
        ]);
    }

    public function iniciar(Request $request, Examen $examen): RedirectResponse
    {
        $usuario = $request->user();

        abort_unless($this->examenAsignado($examen, $usuario), 403);

        $resultado = DB::transaction(function () use ($usuario, $examen) {
            // Serializa los inicios simultáneos del mismo alumno: sin este
            // bloqueo, varias peticiones en paralelo pasan el chequeo de
            // max_intentos a la vez y crean intentos de más.
            User::whereKey($usuario->id)->lockForUpdate()->first();

            $enCurso = $usuario->intentos()
                ->where('examen_id', $examen->id)
                ->where('estado', EstadoIntento::EnCurso)
                ->first();

            if ($enCurso) {
                return $enCurso;
            }

            if (now()->lt($examen->apertura) || now()->gt($examen->cierre)) {
                return 'Este examen no está disponible en este momento.';
            }

            $usados = $usuario->intentos()->where('examen_id', $examen->id)->count();

            if ($usados >= $examen->max_intentos) {
                return 'Ya usaste todos los intentos permitidos para este examen.';
            }

            return IntentoExamen::create([
                'alumno_id' => $usuario->id,
                'examen_id' => $examen->id,
                'inicio' => now(),
                'estado' => EstadoIntento::EnCurso,
                ...$this->generarOrdenAleatorio($examen),
            ]);
        });

        if (is_string($resultado)) {
            return back()->with('error', $resultado);
        }

        return redirect()->route('intentos.responder', $resultado);
    }

    /**
     * Baraja preguntas y, dentro de cada una, sus opciones. Se calcula una
     * sola vez (al crear el intento) y queda guardado: así el orden que ve
     * el alumno es estable durante todo el intento, aunque recargue o
     * navegue de pregunta en pregunta.
     */
    private function generarOrdenAleatorio(Examen $examen): array
    {
        $preguntas = $examen->preguntas()->with('opciones')->get();

        return [
            'orden_preguntas' => $preguntas->pluck('id')->shuffle()->values()->all(),
            'orden_opciones' => $preguntas
                ->mapWithKeys(fn (Pregunta $pregunta) => [
                    $pregunta->id => $pregunta->opciones->pluck('id')->shuffle()->values()->all(),
                ])
                ->all(),
        ];
    }

    /**
     * Punto de entrada genérico ("Rendir"/"Continuar"): manda a la primera
     * pregunta todavía no mostrada, o a la última mostrada si ya no queda
     * ninguna nueva por delante.
     */
    public function responder(Request $request, IntentoExamen $intento): RedirectResponse
    {
        abort_unless($intento->alumno_id === $request->user()->id, 403);
        abort_unless($intento->estado === EstadoIntento::EnCurso, 404);

        $intento->loadMissing('examen');

        if ($this->tiempoAgotado($intento)) {
            $this->finalizar($intento);

            return redirect()->route('intentos.index')
                ->with('status', 'El tiempo del examen se agotó: se entregó automáticamente con lo que tenías guardado.');
        }

        $preguntas = $this->preguntasOrdenadas($intento);
        $posicion = $this->posicionActual($intento, $preguntas);

        return redirect()->route('intentos.pregunta', [$intento, $posicion]);
    }

    /**
     * Muestra UNA pregunta. La primera vez que se pide una posición se crea
     * (o se recupera) su Respuesta con mostrada_en = ahora: ese es el
     * instante desde el que corre el tiempo_seg de esa pregunta puntual.
     * Es de avance estricto: solo se puede ver la posición actual (ni saltar
     * adelante, ni volver a una ya pasada).
     */
    public function mostrarPregunta(Request $request, IntentoExamen $intento, int $posicion): View|RedirectResponse
    {
        abort_unless($intento->alumno_id === $request->user()->id, 403);
        abort_unless($intento->estado === EstadoIntento::EnCurso, 404);

        $intento->loadMissing('examen');

        if ($this->tiempoAgotado($intento)) {
            $this->finalizar($intento);

            return redirect()->route('intentos.index')
                ->with('status', 'El tiempo del examen se agotó: se entregó automáticamente con lo que tenías guardado.');
        }

        $preguntas = $this->preguntasOrdenadas($intento);
        $total = $preguntas->count();

        abort_if($posicion < 1 || $posicion > $total, 404);

        // se puede ver la pregunta "actual" (la última mostrada, por si se
        // recarga la página) o avanzar a la próxima todavía no mostrada;
        // cualquier otra posición (para atrás o salteando adelante) rebota.
        $siguienteNueva = $this->posicionSiguienteNueva($intento, $preguntas);
        $actual = max(1, min($total, $siguienteNueva - 1));

        if ($posicion !== $actual && $posicion !== $siguienteNueva) {
            return redirect()->route('intentos.pregunta', [$intento, $actual])
                ->with('error', 'Esa pregunta ya quedó atrás: no se puede volver a una anterior.');
        }

        $pregunta = $preguntas->get($posicion - 1);

        // firstOrCreate no toca mostrada_en si la fila ya existía: revisitar
        // una pregunta no le reinicia el reloj.
        $respuesta = $intento->respuestas()->firstOrCreate(
            ['pregunta_id' => $pregunta->id],
            ['mostrada_en' => now(), 'estado' => EstadoRespuesta::Pendiente]
        );

        return view('intentos.pregunta', [
            'intento' => $intento,
            'pregunta' => $pregunta,
            'respuesta' => $respuesta,
            'posicion' => $posicion,
            'total' => $total,
            'limiteExamen' => $intento->inicio->copy()->addMinutes($intento->examen->duracion_min),
            'limitePregunta' => $pregunta->tiempo_seg
                ? $respuesta->mostrada_en->copy()->addSeconds($pregunta->tiempo_seg)
                : null,
        ]);
    }

    /**
     * Guarda (o actualiza) la respuesta a UNA pregunta apenas el alumno la
     * contesta. Rechaza el guardado si venció el tiempo general del examen
     * o el propio de la pregunta (contado desde que se mostró).
     */
    public function guardarRespuesta(Request $request, IntentoExamen $intento, Pregunta $pregunta): JsonResponse
    {
        abort_unless($intento->alumno_id === $request->user()->id, 403);

        $request->validate([
            'opcion_id' => ['nullable', 'integer'],
            'texto' => ['nullable', 'string', 'max:10000'],
        ]);

        if ($intento->estado !== EstadoIntento::EnCurso) {
            return response()->json(['guardado' => false, 'motivo' => 'el intento ya no está en curso'], 409);
        }

        $intento->loadMissing('examen');

        if ($this->tiempoAgotado($intento)) {
            $this->finalizar($intento);

            return response()->json(['guardado' => false, 'motivo' => 'se agotó el tiempo del examen'], 409);
        }

        abort_unless($intento->examen->preguntas()->where('preguntas.id', $pregunta->id)->exists(), 404);

        $respuesta = $intento->respuestas()->where('pregunta_id', $pregunta->id)->first();

        // Solo se puede contestar lo que ya se mostró: si no, se saltaría el
        // avance estricto y el temporizador propio de la pregunta.
        if (! $respuesta?->mostrada_en) {
            return response()->json(['guardado' => false, 'motivo' => 'esa pregunta todavía no se mostró'], 409);
        }

        if (
            $pregunta->tiempo_seg
            && now()->gt($respuesta->mostrada_en->copy()->addSeconds($pregunta->tiempo_seg))
        ) {
            return response()->json(['guardado' => false, 'motivo' => 'se agotó el tiempo de esta pregunta'], 409);
        }

        if ($pregunta->tipo === TipoPregunta::OpcionMultiple) {
            $opcion = $pregunta->opciones()->find($request->integer('opcion_id'));

            $intento->respuestas()->updateOrCreate(
                ['pregunta_id' => $pregunta->id],
                [
                    'opcion_id' => $opcion?->id,
                    'es_correcta' => $opcion?->es_correcta ?? false,
                    'puntaje' => $opcion?->es_correcta ? $pregunta->puntaje : 0,
                    'estado' => EstadoRespuesta::Calificada,
                ]
            );
        } else {
            $texto = trim((string) $request->input('texto'));

            $intento->respuestas()->updateOrCreate(
                ['pregunta_id' => $pregunta->id],
                [
                    'texto' => $texto !== '' ? $texto : null,
                    'puntaje' => null,
                    'estado' => EstadoRespuesta::Pendiente,
                ]
            );
        }

        return response()->json(['guardado' => true]);
    }

    /**
     * Registra que el alumno salió de la pestaña/ventana del examen. Al
     * llegar a MAX_SALIDAS_PESTANA el intento se entrega solo con lo ya
     * guardado.
     */
    public function registrarSalidaPestana(Request $request, IntentoExamen $intento): JsonResponse
    {
        abort_unless($intento->alumno_id === $request->user()->id, 403);

        if ($intento->estado !== EstadoIntento::EnCurso) {
            return response()->json(['salidas' => $intento->salidas_pestana, 'entregado' => true], 409);
        }

        $intento->increment('salidas_pestana');
        $intento->refresh();

        $entregado = $intento->salidas_pestana >= self::MAX_SALIDAS_PESTANA;

        if ($entregado) {
            $this->finalizar($intento);
        }

        return response()->json([
            'salidas' => $intento->salidas_pestana,
            'maximo' => self::MAX_SALIDAS_PESTANA,
            'entregado' => $entregado,
        ]);
    }

    public function entregar(Request $request, IntentoExamen $intento): RedirectResponse
    {
        abort_unless($intento->alumno_id === $request->user()->id, 403);
        abort_unless($intento->estado === EstadoIntento::EnCurso, 404);

        $this->finalizar($intento);

        return redirect()->route('intentos.index')->with('status', 'Examen entregado.');
    }

    /**
     * Cierra el intento usando solo lo que ya quedó guardado por
     * guardarRespuesta()/mostrarPregunta(): las preguntas nunca contestadas
     * (mostradas o no) se califican en 0, no quedan "sin respuesta" flotando.
     */
    private function finalizar(IntentoExamen $intento): void
    {
        DB::transaction(function () use ($intento) {
            $intento->loadMissing('examen.preguntas', 'respuestas');

            foreach ($intento->examen->preguntas as $pregunta) {
                $respuesta = $intento->respuestas->firstWhere('pregunta_id', $pregunta->id);

                if (! $respuesta) {
                    $intento->respuestas()->create([
                        'pregunta_id' => $pregunta->id,
                        'estado' => EstadoRespuesta::Calificada,
                        'puntaje' => 0,
                        'es_correcta' => $pregunta->tipo === TipoPregunta::OpcionMultiple ? false : null,
                    ]);

                    continue;
                }

                if ($pregunta->tipo === TipoPregunta::OpcionMultiple && is_null($respuesta->opcion_id)) {
                    $respuesta->update(['estado' => EstadoRespuesta::Calificada, 'puntaje' => 0, 'es_correcta' => false]);
                }

                if ($pregunta->tipo === TipoPregunta::Abierta && blank($respuesta->texto)) {
                    $respuesta->update(['estado' => EstadoRespuesta::Calificada, 'puntaje' => 0]);
                }
            }

            $intento->update(['fin' => now(), 'estado' => EstadoIntento::Entregado]);
            $intento->recalcularCalificacion();
        });
    }

    private function preguntasOrdenadas(IntentoExamen $intento): Collection
    {
        $preguntas = $intento->examen->preguntas()->with('opciones')->get()->keyBy('id');

        $ordenadas = collect($intento->orden_preguntas)
            ->map(function (int $preguntaId) use ($preguntas, $intento) {
                $pregunta = $preguntas->get($preguntaId);
                $ordenOpciones = $intento->orden_opciones[$preguntaId] ?? [];

                $pregunta->setRelation(
                    'opciones',
                    collect($ordenOpciones)
                        ->map(fn (int $opcionId) => $pregunta->opciones->firstWhere('id', $opcionId))
                        ->filter()
                        ->values()
                );

                return $pregunta;
            })
            ->values();

        return Collection::make($ordenadas->all());
    }

    /** Primera posición (1-indexada) todavía no mostrada; total+1 si ya se mostraron todas. */
    private function posicionSiguienteNueva(IntentoExamen $intento, Collection $preguntas): int
    {
        $mostradas = $intento->respuestas()->whereNotNull('mostrada_en')->pluck('pregunta_id');

        foreach ($preguntas as $indice => $pregunta) {
            if (! $mostradas->contains($pregunta->id)) {
                return $indice + 1;
            }
        }

        return $preguntas->count() + 1;
    }

    /** La última pregunta ya mostrada (o la 1 si el intento recién arranca). */
    private function posicionActual(IntentoExamen $intento, Collection $preguntas): int
    {
        $siguienteNueva = $this->posicionSiguienteNueva($intento, $preguntas);

        return max(1, min($preguntas->count(), $siguienteNueva - 1));
    }

    private function tiempoAgotado(IntentoExamen $intento): bool
    {
        return now()->gt($intento->inicio->copy()->addMinutes($intento->examen->duracion_min));
    }

    private function examenAsignado(Examen $examen, $usuario): bool
    {
        return $examen->grupos()
            ->whereIn('grupos.id', $usuario->grupos()->pluck('grupos.id'))
            ->exists();
    }
}
