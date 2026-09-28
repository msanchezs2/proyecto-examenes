<?php

namespace Database\Seeders;

use App\Models\Alumno;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AlumnoSeeder extends Seeder
{
    /** nombre, matricula, promedio */
    private const ALUMNOS = [
        ['Ana Torres', 'A2026001', 8.7],
        ['Bruno Salas', 'A2026002', 7.9],
        ['Camila Rojas', 'A2026003', 9.2],
        ['Diego Fuentes', 'A2026004', 6.8],
        ['Elena Vargas', 'A2026005', 8.1],
        ['Fernando Luna', 'A2026006', 7.4],
        ['Gabriela Cruz', 'A2026007', 9.5],
        ['Hugo Paredes', 'A2026008', 7.0],
    ];

    public function run(): void
    {
        foreach (self::ALUMNOS as [$nombre, $matricula, $promedio]) {
            $email = Str::of($nombre)->slug('.').'@examenes.test';

            $alumno = Alumno::firstOrCreate(
                ['email' => $email],
                ['name' => $nombre, 'password' => Hash::make('password')]
            );

            $alumno->alumnoPerfil()->firstOrCreate([], [
                'matricula' => $matricula,
                'promedio' => $promedio,
            ]);
        }
    }
}
