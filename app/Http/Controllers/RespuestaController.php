<?php

namespace App\Http\Controllers;

use App\Enums\EstadoRespuesta;
use App\Models\Respuesta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RespuestaController extends Controller
{
    public function index(): View
    {
        $pendientes = Respuesta::with(['pregunta', 'intento.alumno', 'intento.examen'])
            ->where('estado', EstadoRespuesta::Pendiente)
            ->orderBy('intento_examen_id')
            ->get()
            ->groupBy('intento_examen_id');

        return view('calificaciones.index', [
            'grupos' => $pendientes,
        ]);
    }

    public function update(Request $request, Respuesta $respuesta): RedirectResponse
    {
        abort_if($respuesta->estado !== EstadoRespuesta::Pendiente, 404);

        $respuesta->loadMissing('pregunta', 'intento');

        $validado = $request->validate([
            'puntaje' => ['required', 'numeric', 'min:0', 'max:'.$respuesta->pregunta->puntaje],
        ]);

        $respuesta->update([
            'puntaje' => $validado['puntaje'],
            'estado' => EstadoRespuesta::Calificada,
            'calificado_por' => $request->user()->id,
            'calificado_en' => now(),
        ]);

        $respuesta->intento->recalcularCalificacion();

        return back()->with('status', 'Respuesta calificada.');
    }
}
