<?php

namespace App\Console\Commands;

use App\Enums\EstadoIntento;
use App\Http\Controllers\IntentoExamenController;
use App\Models\IntentoExamen;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReabrirIntentosPorSalidasPestana extends Command
{
    protected $signature = 'intentos:reabrir-por-salidas
                            {examen : ID del examen a revisar}
                            {--aplicar : Reabre los intentos; sin esta opción solo lista los afectados}
                            {--intentos= : IDs separados por coma; reabre esos intentos aunque no cumplan el criterio de salidas}
                            {--minutos= : Minutos de examen que tendrán desde ahora (por defecto conserva el tiempo restante)}';

    protected $description = 'Reabre los intentos entregados antes de tiempo por el conteo (falso) de salidas de pestaña';

    public function handle(): int
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $this->option('intentos'))));

        $afectados = IntentoExamen::with('examen', 'alumno')
            ->where('examen_id', $this->argument('examen'))
            ->when($ids, fn ($query) => $query->whereIn('id', $ids))
            ->when(! $ids, fn ($query) => $query
                ->where('estado', '!=', EstadoIntento::EnCurso)
                ->where('salidas_pestana', '>=', IntentoExamenController::MAX_SALIDAS_PESTANA))
            ->get()
            ->when(! $ids, fn ($intentos) => $intentos->filter(fn (IntentoExamen $intento) => $intento->fin->lt(
                $intento->inicio->copy()->addMinutes($intento->examen->duracion_min)
            )));

        if ($afectados->isEmpty()) {
            $this->info('No hay intentos entregados antes de tiempo por salidas de pestaña.');

            return self::SUCCESS;
        }

        $this->table(
            ['Intento', 'Alumno', 'Estado', 'Inicio', 'Fin', 'Salidas'],
            $afectados->map(fn (IntentoExamen $intento) => [
                $intento->id,
                $intento->alumno?->email ?? $intento->alumno_id,
                $intento->estado->value,
                $intento->inicio,
                $intento->fin,
                $intento->salidas_pestana,
            ])
        );

        if (! $this->option('aplicar')) {
            $this->warn("{$afectados->count()} intento(s) afectado(s). Vuelve a ejecutar con --aplicar para reabrirlos.");

            return self::SUCCESS;
        }

        $minutos = $this->option('minutos') !== null ? (int) $this->option('minutos') : null;

        $afectados->each(fn (IntentoExamen $intento) => $this->reabrir($intento, $minutos));

        $this->info("{$afectados->count()} intento(s) reabierto(s).");

        return self::SUCCESS;
    }

    /**
     * Devuelve el intento a "en curso" conservando el tiempo que le quedaba
     * al alumno: corre hacia adelante el inicio (y el "mostrada_en" de cada
     * pregunta) por el lapso que estuvo cerrado. Las respuestas que la
     * entrega automática creó en 0 para preguntas nunca mostradas se borran,
     * para que el alumno pueda verlas y contestarlas.
     *
     * Con $minutos, el reloj se reinicia: al alumno le quedan exactamente esos
     * minutos desde ahora y el tiempo propio de la pregunta actual vuelve a
     * empezar (útil si el intento ya se había vuelto a cerrar por tiempo).
     */
    private function reabrir(IntentoExamen $intento, ?int $minutos = null): void
    {
        DB::transaction(function () use ($intento, $minutos) {
            $segundosCerrado = $intento->fin ? (int) $intento->fin->diffInSeconds(now()) : 0;

            $nuevoInicio = $minutos !== null
                ? now()->subMinutes(max(0, $intento->examen->duracion_min - $minutos))
                : $intento->inicio->copy()->addSeconds($segundosCerrado);

            $intento->respuestas()->whereNull('mostrada_en')->delete();

            $intento->respuestas()->whereNotNull('mostrada_en')->get()
                ->each(fn ($respuesta) => $respuesta->update([
                    'mostrada_en' => $minutos !== null
                        ? now()
                        : $respuesta->mostrada_en->copy()->addSeconds($segundosCerrado),
                ]));

            $intento->update([
                'inicio' => $nuevoInicio,
                'fin' => null,
                'estado' => EstadoIntento::EnCurso,
                'salidas_pestana' => 0,
                'calificacion_parcial' => null,
                'calificacion_final' => null,
            ]);
        });
    }
}
