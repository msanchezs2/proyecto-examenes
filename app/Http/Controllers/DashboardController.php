<?php

namespace App\Http\Controllers;

use App\Enums\EstadoRespuesta;
use App\Models\Alumno;
use App\Models\Examen;
use App\Models\Grupo;
use App\Models\Pregunta;
use App\Models\Respuesta;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard.index', [
            'totalGrupos' => Grupo::count(),
            'totalAlumnos' => Alumno::count(),
            'totalPreguntas' => Pregunta::count(),
            'totalExamenes' => Examen::count(),
            'examenesActivos' => Examen::where('apertura', '<=', now())->where('cierre', '>=', now())->count(),
            'pendientesCalificar' => Respuesta::where('estado', EstadoRespuesta::Pendiente)->count(),
        ]);
    }
}
