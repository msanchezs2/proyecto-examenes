<?php

namespace Database\Seeders;

use App\Models\Administrador;
use App\Models\Alumno;
use App\Models\Grupo;
use Illuminate\Database\Seeder;

class GrupoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Administrador::firstOrFail();
        $alumnos = Alumno::orderBy('id')->get();

        $matematicas = Grupo::firstOrCreate(
            ['nombre' => 'Matemáticas 101', 'ciclo' => '2026-A'],
            ['administrado_por' => $admin->id]
        );
        // primeros 6 alumnos
        $matematicas->alumnos()->syncWithoutDetaching($alumnos->slice(0, 6)->pluck('id'));

        $historia = Grupo::firstOrCreate(
            ['nombre' => 'Historia 202', 'ciclo' => '2026-A'],
            ['administrado_por' => $admin->id]
        );
        // alumnos 4 a 8: se solapan con Matemáticas para demostrar la relación N:M
        $historia->alumnos()->syncWithoutDetaching($alumnos->slice(3, 5)->pluck('id'));
    }
}
