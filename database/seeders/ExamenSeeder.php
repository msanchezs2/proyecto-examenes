<?php

namespace Database\Seeders;

use App\Models\Administrador;
use App\Models\Examen;
use App\Models\Grupo;
use App\Models\Pregunta;
use Illuminate\Database\Seeder;

class ExamenSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Administrador::firstOrFail();
        $matematicas = Grupo::where('nombre', 'Matemáticas 101')->firstOrFail();
        $historia = Grupo::where('nombre', 'Historia 202')->firstOrFail();
        $preguntas = Pregunta::all()->keyBy('enunciado');

        $parcialMate = Examen::firstOrCreate(
            ['titulo' => 'Parcial 1 - Matemáticas'],
            [
                'apertura' => now(),
                'cierre' => now()->addDays(7),
                'duracion_min' => 45,
                'max_intentos' => 2,
                'creado_por' => $admin->id,
            ]
        );
        $parcialMate->grupos()->syncWithoutDetaching([$matematicas->id]);
        $parcialMate->preguntas()->syncWithoutDetaching([
            $preguntas['¿Cuánto es 12 × 8?']->id => ['orden' => 1],
            $preguntas['¿Cuál es la raíz cuadrada de 144?']->id => ['orden' => 2],
            $preguntas['Explica el teorema de Pitágoras y da un ejemplo con números.']->id => ['orden' => 3],
        ]);

        $parcialHistoria = Examen::firstOrCreate(
            ['titulo' => 'Parcial 1 - Historia'],
            [
                'apertura' => now(),
                'cierre' => now()->addDays(7),
                'duracion_min' => 40,
                'max_intentos' => 1,
                'creado_por' => $admin->id,
            ]
        );
        $parcialHistoria->grupos()->syncWithoutDetaching([$historia->id]);
        $parcialHistoria->preguntas()->syncWithoutDetaching([
            $preguntas['¿En qué año llegó Cristóbal Colón a América?']->id => ['orden' => 1],
            $preguntas['¿Quién fue el primer presidente de México independiente?']->id => ['orden' => 2],
            $preguntas['Describe dos causas principales de la Revolución Mexicana.']->id => ['orden' => 3],
        ]);

        // Reutiliza preguntas ya usadas en los parciales + una nueva de Geografía,
        // para demostrar que el banco de preguntas es compartido entre exámenes.
        $diagnostico = Examen::firstOrCreate(
            ['titulo' => 'Evaluación Diagnóstica'],
            [
                'apertura' => now(),
                'cierre' => now()->addDays(14),
                'duracion_min' => 30,
                'max_intentos' => 1,
                'creado_por' => $admin->id,
            ]
        );
        $diagnostico->grupos()->syncWithoutDetaching([$matematicas->id, $historia->id]);
        $diagnostico->preguntas()->syncWithoutDetaching([
            $preguntas['¿Cuánto es 12 × 8?']->id => ['orden' => 1],
            $preguntas['¿En qué año llegó Cristóbal Colón a América?']->id => ['orden' => 2],
            $preguntas['¿Cuál es la capital de Francia?']->id => ['orden' => 3],
            $preguntas['Menciona dos consecuencias del cambio climático en zonas costeras.']->id => ['orden' => 4],
        ]);
    }
}
