@extends('layouts.simple')

@section('titulo', 'Mis Exámenes')

@section('content')
    <div class="cabecera">
        <div>
            <h1>Hola, {{ explode(' ', auth()->user()->name)[0] }}</h1>
            <p class="subtitle">Tus exámenes y resultados.</p>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-tile">
            <p class="stat-label">Exámenes disponibles</p>
            <p class="stat-valor">{{ $examenes->count() }}</p>
        </div>
        <div class="stat-tile">
            <p class="stat-label">Promedio</p>
            <p class="stat-valor">{{ $promedio !== null ? number_format($promedio, 1) : '—' }}</p>
        </div>
        <div class="stat-tile {{ $esperandoCorreccion > 0 ? 'alerta' : 'ok' }}">
            <p class="stat-label">Esperando corrección</p>
            <p class="stat-valor">{{ $esperandoCorreccion }}</p>
        </div>
    </div>

    <h2 class="seccion-titulo">Exámenes disponibles</h2>
    @forelse ($examenes as $examen)
        @php($estaProgramado = $examen->apertura->isFuture())
        <article class="card">
            <div class="meta">
                @if ($estaProgramado)
                    <span class="badge badge-tema">Programado</span>
                    <span class="dato">Abre {{ $examen->apertura->format('d/m/Y H:i') }}</span>
                @else
                    @if ($examen->cierre->isPast())
                        <span class="badge badge-tema">Cerrado</span>
                        <span class="dato">Cerró {{ $examen->cierre->format('d/m/Y H:i') }}</span>
                    @else
                        <span class="dato">Cierra {{ $examen->cierre->format('d/m/Y H:i') }}</span>
                    @endif
                    @if ($examen->cierre->isFuture() && now()->diffInHours($examen->cierre) <= 48)
                        <span class="badge badge-abierta">Cierra pronto</span>
                    @endif
                @endif
                <span class="dato">{{ $examen->duracion_min }} min</span>
                <span class="dato">{{ $examen->intentos_usados }}/{{ $examen->max_intentos }} intento(s) usados</span>
            </div>
            <p class="enunciado" style="font-weight:600; font-size:16px;">{{ $examen->titulo }}</p>

            @if ($estaProgramado)
                <button
                    type="button"
                    class="boton secundario"
                    disabled
                    data-boton-programado
                    data-apertura="{{ $examen->apertura->toISOString() }}"
                >
                    Disponible en <span class="cuenta-apertura">calculando…</span>
                </button>
            @elseif ($examen->intentoEnCurso)
                <a class="boton" href="{{ route('intentos.responder', $examen->intentoEnCurso) }}">Continuar intento en curso</a>
            @elseif ($examen->intentos_usados < $examen->max_intentos)
                <form method="POST" action="{{ route('intentos.iniciar', $examen) }}">
                    @csrf
                    <button type="submit" class="boton">Rendir examen</button>
                </form>
            @else
                <span class="dato">Ya usaste todos tus intentos para este examen.</span>
            @endif
        </article>
    @empty
        <p class="vacio">No tenés exámenes disponibles en este momento.</p>
    @endforelse

    <h2 class="seccion-titulo">Mis resultados</h2>
    @forelse ($intentos as $intento)
        <article class="card">
            <div class="meta">
                <span class="badge badge-tema">{{ $intento->examen->titulo }}</span>
                <span class="dato">{{ $intento->inicio->format('d/m/Y H:i') }}</span>
            </div>

            @switch($intento->estado)
                @case(\App\Enums\EstadoIntento::EnCurso)
                    <span class="badge badge-abierta">En curso</span>
                    @break

                @case(\App\Enums\EstadoIntento::Entregado)
                    <span class="badge badge-abierta">Entregado, esperando corrección</span>
                    @break

                @case(\App\Enums\EstadoIntento::CalificacionParcial)
                    <span class="badge badge-abierta">Parcial: {{ $intento->calificacion_parcial }} pts (faltan preguntas abiertas por corregir)</span>
                    @break

                @case(\App\Enums\EstadoIntento::Calificado)
                    <span class="badge badge-opcion_multiple">Nota final: {{ $intento->calificacion_final }} pts</span>
                    @break
            @endswitch
        </article>
    @empty
        <p class="vacio">Todavía no rendiste ningún examen.</p>
    @endforelse

    <script nonce="{{ Vite::cspNonce() }}">
        (function () {
            function formatearRestante(ms) {
                const totalSeg = Math.max(0, Math.floor(ms / 1000));
                const dias = Math.floor(totalSeg / 86400);
                const horas = Math.floor((totalSeg % 86400) / 3600);
                const minutos = Math.floor((totalSeg % 3600) / 60);
                const segundos = totalSeg % 60;
                const reloj = [horas, minutos, segundos].map(function (n) {
                    return String(n).padStart(2, '0');
                }).join(':');

                return dias > 0 ? dias + 'd ' + reloj : reloj;
            }

            document.querySelectorAll('[data-boton-programado]').forEach(function (boton) {
                const apertura = new Date(boton.dataset.apertura).getTime();
                const etiqueta = boton.querySelector('.cuenta-apertura');

                const intervalo = setInterval(function () {
                    const restante = apertura - Date.now();

                    if (restante <= 0) {
                        clearInterval(intervalo);
                        window.location.reload();
                        return;
                    }

                    if (etiqueta) {
                        etiqueta.textContent = formatearRestante(restante);
                    }
                }, 1000);
            });
        })();
    </script>
@endsection
