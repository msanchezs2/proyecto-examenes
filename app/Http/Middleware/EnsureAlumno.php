<?php

namespace App\Http\Middleware;

use App\Enums\RolUsuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAlumno
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== RolUsuario::Alumno) {
            abort(403, 'Esta sección es solo para alumnos.');
        }

        return $next($request);
    }
}
