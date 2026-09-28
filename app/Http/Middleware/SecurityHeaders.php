<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Debe generarse antes de renderizar: las vistas lo leen con Vite::cspNonce().
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // same-origin: el token del enlace de restablecer contraseña va en la URL.
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // La página de errores de depuración de Laravel usa scripts y estilos
        // en línea sin nonce; solo se muestra con APP_DEBUG=true.
        if (! ($response->isServerError() && config('app.debug'))) {
            $response->headers->set('Content-Security-Policy', $this->politicaContenido($nonce));
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function politicaContenido(string $nonce): string
    {
        $origenVite = '';
        $websocketVite = '';

        // Solo con "npm run dev" en local: el servidor de Vite sirve JS/CSS
        // desde otro origen y su HMR inyecta estilos sin nonce.
        if (app()->environment('local') && Vite::isRunningHot()) {
            $origenVite = trim((string) file_get_contents(Vite::hotFile()));
            $websocketVite = preg_replace('#^http#', 'ws', $origenVite);
        }

        $estilos = $origenVite !== '' ? "'unsafe-inline' {$origenVite}" : "'nonce-{$nonce}'";

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' {$origenVite}",
            "style-src 'self' {$estilos}",
            "style-src-attr 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self' data: {$origenVite}",
            "connect-src 'self' {$origenVite} {$websocketVite}",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);
    }
}
