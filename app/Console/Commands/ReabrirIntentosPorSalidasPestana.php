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
                            {--aplicar : Reabre los intentos; sin esta opción solo lista los afectados}';

    protected $description = 'Reabre los intentos entregados antes de tiempo por el conteo (falso) de salidas de pestaña';

    public function handle(): int
    {
        $afectados = IntentoExamen::with('examen', 'alumno')
            ->where('examen_id', $this->argument('examen'))
            ->where('estado', '!=', EstadoIntento::EnCurso)
            ->where('salidas_pestana', '>=', IntentoExamenController::MAX_SALIDAS_PESTANA)
            ->get()
            ->filter(fn (IntentoExamen $intento) => $intento->fin->lt(
                $intento->inicio->copy()->addMinutes($intento->examen->duracion_min)
            ));

        if ($afectados->isEmpty()) {
            $this->info('No hay intentos entregados antes de tiempo por salidas de pestaña.');

            return self::SUCCESS;
        }

        $this->table(
            ['Intento', 'Alumno', 'Inicio', 'Fin', 'Salidas'],
            $afectados->map(fn (IntentoExamen $intento) => [
                $intento->id,
                $intento->alumno?->email ?? $intento->alumno_id,
                $intento->inicio,
                $intento->fin,
                $intento->salidas_pestana,
            ])
        );

        if (! $this->option('aplicar')) {
            $this->warn("{$afectados->count()} intento(s) afectado(s). Vuelve a ejecutar con --aplicar para reabrirlos.");

            return self::SUCCESS;
        }

        $afectados->each(fn (IntentoExamen $intento) => $this->reabrir($intento));

        $this->info("{$afectados->count()} intento(s) reabierto(s).");

        return self::SUCCESS;
    }

    /**
     * Devuelve el intento a "en curso" conservando el tiempo que le quedaba
     * al alumno: corre hacia adelante el inicio (y el "mostrada_en" de cada
     * pregunta) por el lapso que estuvo cerrado. Las respuestas que la
     * entrega automática creó en 0 para preguntas nunca mostradas se borran,
     * para que el alumno pueda verlas y contestarlas.
     */
    private function reabrir(IntentoExamen $intento): void
    {
        DB::transaction(function () use ($intento) {
            $segundosCerrado = (int) $intento->fin->diffInSeconds(now());

            $intento->respuestas()->whereNull('mostrada_en')->delete();

            $intento->respuestas()->whereNotNull('mostrada_en')->get()
                ->each(fn ($respuesta) => $respuesta->update([
                    'mostrada_en' => $respuesta->mostrada_en->copy()->addSeconds($segundosCerrado),
                ]));

            $intento->update([
                'inicio' => $intento->inicio->copy()->addSeconds($segundosCerrado),
                'fin' => null,
                'estado' => EstadoIntento::EnCurso,
                'salidas_pestana' => 0,
                'calificacion_parcial' => null,
                'calificacion_final' => null,
            ]);
        });
    }
}
