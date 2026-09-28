<?php

namespace Database\Seeders;

use App\Enums\EstadoIntento;
use App\Enums\EstadoRespuesta;
use App\Models\Administrador;
use App\Models\Alumno;
use App\Models\Examen;
use App\Models\IntentoExamen;
use App\Models\Pregunta;
use Illuminate\Database\Seeder;

class IntentoExamenSeeder extends Seeder
{
    public function run(): void
    {
        if (IntentoExamen::query()->exists()) {
            $this->command?->info('IntentoExamen ya tiene datos, se omite IntentoExamenSeeder.');

            return;
        }

        $admin = Administrador::firstOrFail();
        $alumnos = Alumno::orderBy('id')->get();

        $parcialMate = Examen::where('titulo', 'Parcial 1 - Matemáticas')->firstOrFail();
        $parcialHistoria = Examen::where('titulo', 'Parcial 1 - Historia')->firstOrFail();

        [$q1, $q2, $q3] = $parcialMate->preguntas()->orderBy('examen_pregunta.orden')->get()->all();
        [$q4, $q5, $q6] = $parcialHistoria->preguntas()->orderBy('examen_pregunta.orden')->get()->all();

        // --- Intento 1: CALIFICADO — todo revisado, incluida la pregunta abierta ---
        $intento1 = IntentoExamen::create([
            'alumno_id' => $alumnos[0]->id,
            'examen_id' => $parcialMate->id,
            'inicio' => now()->subHours(3),
            'fin' => now()->subHours(2)->subMinutes(15),
            'estado' => EstadoIntento::Calificado,
            'calificacion_parcial' => 20,
            'calificacion_final' => 38,
        ]);
        $this->responderOpcionMultiple($intento1, $q1, correcta: true);
        $this->responderOpcionMultiple($intento1, $q2, correcta: true);
        $this->responderAbierta(
            $intento1, $q3,
            texto: 'El teorema de Pitágoras dice que en un triángulo rectángulo, el cuadrado de la hipotenusa es igual a la suma de los cuadrados de los catetos. Ejemplo: catetos 3 y 4, hipotenusa 5, porque 3²+4²=9+16=25=5².',
            estado: EstadoRespuesta::Calificada,
            puntaje: 18,
            calificadoPor: $admin,
        );

        // --- Intento 2: CALIFICACION_PARCIAL — opción múltiple ya calificada, abierta pendiente ---
        $intento2 = IntentoExamen::create([
            'alumno_id' => $alumnos[1]->id,
            'examen_id' => $parcialMate->id,
            'inicio' => now()->subHour(),
            'fin' => now()->subMinutes(40),
            'estado' => EstadoIntento::CalificacionParcial,
            'calificacion_parcial' => 10,
        ]);
        $this->responderOpcionMultiple($intento2, $q1, correcta: false);
        $this->responderOpcionMultiple($intento2, $q2, correcta: true);
        $this->responderAbierta(
            $intento2, $q3,
            texto: 'Sirve para calcular lados de triángulos rectángulos.',
            estado: EstadoRespuesta::Pendiente,
        );

        // --- Intento 3: EN_CURSO — todavía respondiendo, sin entregar ---
        $intento3 = IntentoExamen::create([
            'alumno_id' => $alumnos[2]->id,
            'examen_id' => $parcialMate->id,
            'inicio' => now()->subMinutes(10),
            'estado' => EstadoIntento::EnCurso,
        ]);
        $this->responderOpcionMultiple($intento3, $q1, correcta: true);

        // --- Intento 4: ENTREGADO — recién enviado, todavía sin procesar/calificar ---
        $intento4 = IntentoExamen::create([
            'alumno_id' => $alumnos[3]->id,
            'examen_id' => $parcialHistoria->id,
            'inicio' => now()->subMinutes(35),
            'fin' => now()->subMinutes(2),
            'estado' => EstadoIntento::Entregado,
        ]);
        $this->responderOpcionMultiple($intento4, $q4, correcta: true);
        $this->responderOpcionMultiple($intento4, $q5, correcta: false);
        $this->responderAbierta(
            $intento4, $q6,
            texto: 'La desigualdad social, la falta de tierras para los campesinos y la dictadura prolongada de Porfirio Díaz.',
            estado: EstadoRespuesta::Pendiente,
        );
    }

    private function responderOpcionMultiple(IntentoExamen $intento, Pregunta $pregunta, bool $correcta): void
    {
        $opcion = $pregunta->opciones()->where('es_correcta', $correcta)->firstOrFail();

        $intento->respuestas()->create([
            'pregunta_id' => $pregunta->id,
            'opcion_id' => $opcion->id,
            'es_correcta' => $opcion->es_correcta,
            'puntaje' => $opcion->es_correcta ? $pregunta->puntaje : 0,
            'estado' => EstadoRespuesta::Calificada,
        ]);
    }

    private function responderAbierta(
        IntentoExamen $intento,
        Pregunta $pregunta,
        string $texto,
        EstadoRespuesta $estado,
        ?float $puntaje = null,
        ?Administrador $calificadoPor = null,
    ): void {
        $intento->respuestas()->create([
            'pregunta_id' => $pregunta->id,
            'texto' => $texto,
            'puntaje' => $puntaje,
            'estado' => $estado,
            'calificado_por' => $calificadoPor?->id,
            'calificado_en' => $calificadoPor ? now() : null,
        ]);
    }
}
