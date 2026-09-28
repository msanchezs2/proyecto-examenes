<?php

use App\Http\Middleware\EnsureAdministrador;
use App\Http\Middleware\EnsureAlumno;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // En Cloud (y en cualquier despliegue detrás de un balanceador) solo
        // ese proxy le habla a la app directamente: sin esto, $request->ip(),
        // el HTTPS detectado y el límite de intentos por IP del login
        // quedarían todos leyendo la IP del balanceador, no la del cliente.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO);

        $middleware->web(append: [SecurityHeaders::class]);

        $middleware->alias([
            'admin' => EnsureAdministrador::class,
            'alumno' => EnsureAlumno::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
