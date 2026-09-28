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
            max-width: 880px;
            margin: 0 auto;
            padding: 40px 20px 80px;
        }
        h1 { margin: 0 0 4px; }
        .subtitle { color: var(--muted); margin: 0; font-size: 14px; }

        .cabecera {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 20px;
        }

        .meta {
            display: flex;
            gap: 8px;
            align-items: center;
            font-size: 12px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }
        .badge {
            padding: 2px 9px;
            border-radius: 999px;
            font-weight: 600;
        }
        .badge-tema { background: var(--accent-soft); color: var(--accent); }
        .badge-abierta { background: var(--warning-soft); color: var(--warning); }
        .badge-opcion_multiple { background: var(--success-soft); color: var(--success); }
        .badge-neutro { background: var(--surface-alt); color: var(--muted); }
        .meta .dato { color: var(--muted); }

        .enunciado { margin: 0 0 8px; font-size: 15px; line-height: 1.5; }

        ul.opciones { list-style: none; margin: 0 0 10px; padding: 0; display: grid; gap: 4px; }
        ul.opciones li {
            padding: 6px 10px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 13.5px;
            background: var(--surface-alt);
        }
        ul.opciones li.correcta {
            border-color: var(--success-border);
            background: var(--success-soft);
            font-weight: 600;
            color: var(--success);
        }

        .vacio { color: var(--muted); font-style: italic; }
        .contador { color: var(--muted); font-size: 13px; }

        .tabla-wrap {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            overflow-x: auto;
        }
        table.tabla { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        table.tabla th, table.tabla td { padding: 10px 14px; text-align: left; white-space: nowrap; }
        table.tabla th {
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
        }
        table.tabla tbody tr:not(:last-child) td { border-bottom: 1px solid var(--border); }
        table.tabla td.col-acciones { display: flex; gap: 8px; }
        table.tabla .boton, table.tabla button[type="submit"] { padding: 6px 12px; font-size: 12.5px; }

        .form-filtros { display: flex; gap: 10px; margin-bottom: 20px; }
        .form-filtros select { width: auto; margin-top: 0; }

        .seccion-titulo { font-size: 16px; margin: 30px 0 12px; }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 8px;
        }
        .stat-tile {
            display: block;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 16px 18px;
            box-shadow: var(--shadow-sm);
            text-decoration: none;
            color: inherit;
        }
        a.stat-tile-link { transition: border-color .15s, box-shadow .15s; }
        a.stat-tile-link:hover { border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }
        .stat-tile .stat-label { margin: 0 0 6px; font-size: 12.5px; color: var(--muted); }
        .stat-tile .stat-valor { margin: 0; font-size: 28px; font-weight: 600; line-height: 1.1; }
        .stat-tile .stat-sobre { font-size: 14px; font-weight: 400; color: var(--muted); }
        .stat-tile.alerta { border-color: var(--warning-border); background: var(--warning-soft); }
        .stat-tile.alerta .stat-valor { color: var(--warning); }
        .stat-tile.ok .stat-valor { color: var(--success); }

        .accesos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 8px;
        }
        .acceso-card {
            display: flex;
            flex-direction: column;
            gap: 4px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 16px 18px;
            text-decoration: none;
            color: var(--ink);
            box-shadow: var(--shadow-sm);
            transition: border-color .15s, background .15s;
        }
        .acceso-card:hover { border-color: var(--accent); background: var(--accent-soft); }
        .acceso-card .acceso-titulo { font-weight: 600; font-size: 14.5px; }
        .acceso-card .acceso-desc { font-size: 12.5px; color: var(--muted); }

        form.form-pregunta {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 22px;
            box-shadow: var(--shadow-sm);
        }
        form.form-pregunta label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 14px; }
        .fila { display: flex; gap: 16px; }
        .fila > label { flex: 1; }

        #seccion-opciones { margin: 6px 0 18px; padding-top: 12px; border-top: 1px solid var(--border); }
        .ayuda { font-size: 12.5px; color: var(--muted); margin: 0 0 10px; }
        .fila-opcion { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
        .fila-opcion input[type="text"] { margin: 0; flex: 1; }
        .fila-opcion .quitar-opcion {
            font: inherit; font-size: 12px; border: none; background: var(--danger-soft); color: var(--danger);
            padding: 6px 10px; border-radius: 6px; cursor: pointer;
        }
        #agregar-opcion {
            font: inherit; font-size: 13px; border: 1px dashed var(--border-strong); background: var(--surface-alt);
            padding: 7px 12px; border-radius: 8px; cursor: pointer; color: var(--ink);
        }

        .acciones { display: flex; gap: 10px; align-items: center; margin-top: 6px; }
        .acciones a:not(.boton) { color: var(--muted); font-size: 14px; text-decoration: none; }

        .nav-principal {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: -40px -20px 30px;
            padding: 14px 20px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }
        .nav-marca { display: flex; align-items: center; gap: 8px; font-weight: 700; color: var(--ink); text-decoration: none; }
        .nav-marca .punto { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); box-shadow: 0 0 0 3px var(--accent-soft); }
        .nav-acciones { display: flex; align-items: center; gap: 14px; font-size: 13.5px; }
        .nav-acciones a { color: var(--accent); text-decoration: none; font-weight: 600; }
        .nav-acciones a:hover { color: var(--accent-strong); }
        .nav-usuario { color: var(--muted); }
        .nav-acciones button.boton.secundario { font-size: 13.5px; padding: 6px 12px; }

        .lista-check {
            display: flex;
            flex-direction: column;
            gap: 2px;
            max-height: 260px;
            overflow-y: auto;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 16px;
            background: var(--surface-alt);
        }
        .lista-check .check-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 400;
            font-size: 13.5px;
            padding: 5px 4px;
            margin: 0;
        }
        .lista-check .check-item input[type="checkbox"] { width: auto; margin: 0; }
        .lista-check .grupo-tema {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--muted);
            margin: 10px 0 2px;
        }
        .etiqueta-seccion { font-size: 13px; font-weight: 600; margin: 0 0 8px; }

        .respuesta-item { border-top: 1px solid var(--border); padding-top: 12px; margin-top: 12px; }
        .respuesta-item:first-of-type { border-top: none; padding-top: 0; margin-top: 0; }
        .respuesta-texto {
            background: var(--surface-alt); border: 1px solid var(--border); border-radius: 8px;
            padding: 10px; font-size: 14px; line-height: 1.5; margin: 6px 0 10px;
        }
        .form-calificar { display: flex; gap: 10px; align-items: center; }
        .form-calificar label { display: flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 400; margin: 0; }
        .form-calificar input[type="number"] { width: 90px; }
        .form-calificar button { padding: 7px 14px; font-size: 13.5px; }

        .barra-progreso { display: flex; gap: 4px; margin-bottom: 16px; }
        .barra-progreso .paso { flex: 1; height: 5px; border-radius: 3px; background: var(--border); }
        .barra-progreso .paso-hecho { background: var(--accent); }
        .barra-progreso .paso-actual { background: var(--accent-soft); box-shadow: inset 0 0 0 1.5px var(--accent); }

        /* opciones de opción múltiple en la pantalla de rendir examen */
        .opciones-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 10px;
            margin: 4px 0 2px;
        }
        .opcion-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            background: var(--surface);
            cursor: pointer;
            font-size: 14.5px;
            line-height: 1.4;
            transition: border-color .15s, background .15s, box-shadow .15s;
        }
        .opcion-card:hover { border-color: var(--accent); background: var(--accent-soft); }
        .opcion-card:has(input:checked) {
            border-color: var(--accent);
            background: var(--accent-soft);
            box-shadow: 0 0 0 1px var(--accent);
        }
        .opcion-card:has(input:disabled) { opacity: .55; cursor: not-allowed; }
        .opcion-card:has(input:disabled):hover { border-color: var(--border); background: var(--surface); }
        .opcion-card input[type="radio"] {
            appearance: none;
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            margin: 0;
            border-radius: 50%;
            border: 2px solid var(--border-strong);
            flex-shrink: 0;
            display: grid;
            place-content: center;
            transition: border-color .15s;
        }
        .opcion-card input[type="radio"]::before {
            content: '';
            width: 10px;
            height: 10px;
            border-radius: 50%;
            transform: scale(0);
            transition: transform .1s ease;
            background: var(--accent);
        }
        .opcion-card input[type="radio"]:checked { border-color: var(--accent); }
        .opcion-card input[type="radio"]:checked::before { transform: scale(1); }
    </style>
    @yield('estilos')
</head>
<body>
    @php
        $inicio = match (auth()->user()?->role) {
            \App\Enums\RolUsuario::Administrador => route('dashboard'),
            \App\Enums\RolUsuario::Alumno => route('intentos.index'),
            default => route('welcome'),
        };
    @endphp
    <nav class="nav-principal">
        <a href="{{ $inicio }}" class="nav-marca"><span class="punto"></span> Sistema de Exámenes</a>
        <div class="nav-acciones">
            @auth
                <span class="nav-usuario">{{ auth()->user()->name }} · {{ auth()->user()->role->value }}</span>
                @if (auth()->user()->role === \App\Enums\RolUsuario::Administrador)
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                    <a href="{{ route('usuarios.index') }}">Usuarios</a>
                    <a href="{{ route('preguntas.index') }}">Preguntas</a>
                    <a href="{{ route('grupos.index') }}">Grupos</a>
                    <a href="{{ route('examenes.index') }}">Exámenes</a>
                    <a href="{{ route('calificaciones.index') }}">Calificar</a>
                @elseif (auth()->user()->role === \App\Enums\RolUsuario::Alumno)
                    <a href="{{ route('intentos.index') }}">Mis exámenes</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="boton secundario">Salir</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="boton secundario">Iniciar sesión</a>
            @endauth
            @include('layouts._tema-boton')
        </div>
    </nav>

    @if (session('status'))
        <div class="aviso">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="errores">{{ session('error') }}</div>
    @endif

    @yield('content')

    <script nonce="{{ Vite::cspNonce() }}">
        document.addEventListener('change', function (evento) {
            if (evento.target.matches('[data-auto-enviar]')) {
                evento.target.form.submit();
            }
        });

        document.addEventListener('submit', function (evento) {
            const mensaje = evento.target.dataset.confirmar;
            if (mensaje && !confirm(mensaje)) {
                evento.preventDefault();
            }
        });
    </script>
</body>
</html>
