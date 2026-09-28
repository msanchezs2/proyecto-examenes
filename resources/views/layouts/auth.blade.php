<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Sistema de Exámenes')</title>
    @include('layouts._tokens')
    @include('layouts._componentes')
    <style nonce="{{ Vite::cspNonce() }}">
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background:
                radial-gradient(1100px 550px at 12% -10%, var(--accent-soft), transparent 60%),
                var(--bg);
        }
        .auth-card {
            width: 100%;
            max-width: 380px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: 34px 32px 30px;
        }
        .auth-encabezado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 26px;
        }
        .auth-encabezado .boton.secundario { font-size: 12.5px; padding: 6px 11px; }
        .auth-marca {
            display: flex;
            align-items: center;
            gap: 9px;
            font-weight: 700;
            font-size: 14.5px;
            color: var(--ink);
            text-decoration: none;
        }
        .auth-marca .punto {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 0 4px var(--accent-soft);
        }
        .auth-titulo { margin: 0 0 4px; font-size: 21px; }
        .auth-subtitulo { margin: 0 0 22px; color: var(--muted); font-size: 13.5px; }
        .auth-form label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 16px; }
        .auth-fila { display: flex; justify-content: space-between; align-items: center; margin: -6px 0 20px; font-size: 13px; }
        .auth-check { display: flex; align-items: center; gap: 6px; font-weight: 400 !important; margin: 0 !important; }
        .auth-check input { width: auto; margin: 0; }
        .auth-link { color: var(--accent); text-decoration: none; font-weight: 600; }
        .auth-link:hover { text-decoration: underline; }
        .auth-submit { width: 100%; padding: 11px; font-size: 14.5px; }
        .auth-pie { margin-top: 20px; font-size: 13.5px; text-align: center; color: var(--muted); }
    </style>
    @yield('estilos')
</head>
<body>
    <div class="auth-card">
        <div class="auth-encabezado">
            <a href="{{ route('welcome') }}" class="auth-marca"><span class="punto"></span> Sistema de Exámenes</a>
            @include('layouts._tema-boton')
        </div>
        @yield('content')
    </div>
</body>
</html>
