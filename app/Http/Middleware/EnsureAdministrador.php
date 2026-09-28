<?php

namespace App\Http\Middleware;

use App\Enums\RolUsuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdministrador
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== RolUsuario::Administrador) {
            abort(403, 'Solo un administrador puede acceder a esta sección.');
        }

        return $next($request);
    }
}
