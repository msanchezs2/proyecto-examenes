<?php

namespace Database\Seeders;

use App\Enums\TipoPregunta;
use App\Models\Administrador;
use App\Models\Pregunta;
use Illuminate\Database\Seeder;

class PreguntaSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Administrador::firstOrFail();

        $this->opcionMultiple($admin, 'Matemáticas', '¿Cuánto es 12 × 8?', 60, 10, [
            ['86', false], ['96', true], ['102', false], ['108', false],
        ]);

        $this->opcionMultiple($admin, 'Matemáticas', '¿Cuál es la raíz cuadrada de 144?', 45, 10, [
            ['10', false], ['11', false], ['12', true], ['14', false],
        ]);

        $this->abierta($admin, 'Matemáticas', 'Explica el teorema de Pitágoras y da un ejemplo con números.', 180, 20);

        $this->opcionMultiple($admin, 'Historia', '¿En qué año llegó Cristóbal Colón a América?', 45, 10, [
            ['1490', false], ['1492', true], ['1498', false], ['1500', false],
        ]);

        $this->opcionMultiple($admin, 'Historia', '¿Quién fue el primer presidente de México independiente?', 60, 10, [
            ['Guadalupe Victoria', true], ['Porfirio Díaz', false], ['Benito Juárez', false], ['Antonio López de Santa Anna', false],
        ]);

        $this->abierta($admin, 'Historia', 'Describe dos causas principales de la Revolución Mexicana.', 240, 20);

        $this->opcionMultiple($admin, 'Geografía', '¿Cuál es la capital de Francia?', 30, 10, [
            ['Madrid', false], ['París', true], ['Roma', false], ['Berlín', false],
        ]);

        $this->abierta($admin, 'Geografía', 'Menciona dos consecuencias del cambio climático en zonas costeras.', 180, 15);
    }

    private function opcionMultiple(Administrador $admin, string $tema, string $enunciado, int $tiempoSeg, float $puntaje, array $opciones): void
    {
        $pregunta = Pregunta::firstOrCreate(
            ['enunciado' => $enunciado],
            [
                'tipo' => TipoPregunta::OpcionMultiple,
                'tema' => $tema,
                'tiempo_seg' => $tiempoSeg,
                'puntaje' => $puntaje,
                'creado_por' => $admin->id,
            ]
        );

        if ($pregunta->opciones()->doesntExist()) {
            foreach ($opciones as [$texto, $esCorrecta]) {
                $pregunta->opciones()->create(['texto' => $texto, 'es_correcta' => $esCorrecta]);
            }
        }
    }

    private function abierta(Administrador $admin, string $tema, string $enunciado, int $tiempoSeg, float $puntaje): void
    {
        Pregunta::firstOrCreate(
            ['enunciado' => $enunciado],
            [
                'tipo' => TipoPregunta::Abierta,
                'tema' => $tema,
                'tiempo_seg' => $tiempoSeg,
                'puntaje' => $puntaje,
                'creado_por' => $admin->id,
            ]
        );
    }
}
