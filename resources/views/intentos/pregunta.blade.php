@extends('layouts.simple')

@section('titulo', 'Pregunta '.$posicion.' de '.$total)

@section('content')
    @php($segundosPregunta = $limitePregunta ? max(0, $limitePregunta->getTimestamp() - now()->getTimestamp()) : null)

    <div class="cabecera">
        <div>
            <h1>{{ $intento->examen->titulo }}</h1>
            <p class="subtitle">
                Pregunta {{ $posicion }} de {{ $total }}
                · Tiempo total restante: <strong id="tiempo-restante">--:--</strong>
                · <span id="estado-guardado">tus respuestas se guardan solas</span>
                · una vez que avanzás no podés volver atrás
            </p>
        </div>
    </div>

    <div class="barra-progreso">
        @for ($i = 1; $i <= $total; $i++)
            <span class="paso @if ($i < $posicion) paso-hecho @elseif ($i === $posicion) paso-actual @endif"></span>
        @endfor
    </div>

    <div
        class="card"
        id="pregunta"
        data-pregunta-id="{{ $pregunta->id }}"
        @if ($limitePregunta)
            data-limite="{{ $limitePregunta->toISOString() }}"
        @endif
    >
        <div class="meta">
            <span class="dato">{{ $pregunta->puntaje }} pts</span>
            @if ($pregunta->tiempo_seg)
                <span class="badge badge-tema">quedan <span class="cuenta-pregunta">{{ $segundosPregunta }}</span>s</span>
            @endif
        </div>
        <p class="enunciado" style="font-weight:600; font-size:17px;">{{ $pregunta->enunciado }}</p>

        @if ($pregunta->tipo === \App\Enums\TipoPregunta::OpcionMultiple)
            <div class="opciones-grid">
                @foreach ($pregunta->opciones as $opcion)
                    <label class="opcion-card">
                        <input type="radio" name="opcion" value="{{ $opcion->id }}" @checked($respuesta->opcion_id === $opcion->id)>
                        <span>{{ $opcion->texto }}</span>
                    </label>
                @endforeach
            </div>
        @else
            <textarea id="campo-texto" rows="5" maxlength="10000" placeholder="Escribí tu respuesta...">{{ $respuesta->texto }}</textarea>
        @endif
    </div>

    <form method="POST" action="{{ route('intentos.entregar', $intento) }}" id="form-entregar">
        @csrf
        <div class="acciones" style="margin-top:16px;">
            @if ($posicion < $total)
                <a class="boton nav-pregunta" id="boton-siguiente" href="{{ route('intentos.pregunta', [$intento, $posicion + 1]) }}">Siguiente &rarr;</a>
            @else
                <button type="submit" class="boton">Entregar examen</button>
            @endif
        </div>
    </form>

    <script nonce="{{ Vite::cspNonce() }}">
        (function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const contenedor = document.getElementById('pregunta');
            const guardarUrl = "{{ url('/intentos/'.$intento->id.'/respuestas/'.$pregunta->id) }}";
            const estadoGuardado = document.getElementById('estado-guardado');
            const radios = contenedor.querySelectorAll('input[type="radio"]');
            const textarea = document.getElementById('campo-texto');
            const formEntregar = document.getElementById('form-entregar');
            const enlaceSiguiente = document.getElementById('boton-siguiente');

            let guardadoPendiente = Promise.resolve();
            let temporizadorTexto = null;
            let yaSeFue = false;

            function guardar(datos) {
                guardadoPendiente = fetch(guardarUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(datos),
                })
                    .then(function (respuesta) {
                        estadoGuardado.textContent = respuesta.ok ? 'guardado ✓' : 'no se pudo guardar (¿se acabó el tiempo?)';
                        return respuesta;
                    })
                    .catch(function () {
                        estadoGuardado.textContent = 'sin conexión, reintentá';
                    });

                return guardadoPendiente;
            }

            radios.forEach(function (radio) {
                radio.addEventListener('change', function () {
                    guardar({opcion_id: radio.value});
                });
            });

            if (textarea) {
                textarea.addEventListener('input', function () {
                    clearTimeout(temporizadorTexto);
                    temporizadorTexto = setTimeout(function () {
                        guardar({texto: textarea.value});
                    }, 800);
                });
            }

            function flushPendiente() {
                clearTimeout(temporizadorTexto);
                if (textarea) {
                    guardar({texto: textarea.value});
                }
                return guardadoPendiente;
            }

            function irA(url) {
                if (yaSeFue) return;
                yaSeFue = true;
                flushPendiente().finally(function () {
                    window.location.href = url;
                });
            }

            function entregar() {
                if (yaSeFue) return;
                yaSeFue = true;
                flushPendiente().finally(function () {
                    formEntregar.submit();
                });
            }

            document.querySelectorAll('.nav-pregunta').forEach(function (enlace) {
                enlace.addEventListener('click', function (evento) {
                    evento.preventDefault();
                    irA(enlace.href);
                });
            });

            formEntregar.addEventListener('submit', function (evento) {
                if (yaSeFue) return;
                evento.preventDefault();
                entregar();
            });

            // temporizador propio de esta pregunta: bloquea y avanza sola al llegar a 0
            const limite = contenedor.dataset.limite;
            if (limite) {
                const limiteMs = new Date(limite).getTime();
                const badge = contenedor.querySelector('.cuenta-pregunta');

                const intervalo = setInterval(function () {
                    const restante = Math.max(0, Math.round((limiteMs - Date.now()) / 1000));
                    if (badge) {
                        badge.textContent = restante;
                    }

                    if (restante <= 0) {
                        clearInterval(intervalo);
                        radios.forEach(function (radio) { radio.disabled = true; });
                        if (textarea) {
                            textarea.disabled = true;
                        }
                        contenedor.style.opacity = '0.6';

                        setTimeout(function () {
                            if (enlaceSiguiente) {
                                irA(enlaceSiguiente.href);
                            } else {
                                entregar();
                            }
                        }, 600);
                    }
                }, 1000);
            }

            // temporizador general del examen completo
            const limiteExamen = new Date("{{ $limiteExamen->toISOString() }}").getTime();
            const spanGeneral = document.getElementById('tiempo-restante');

            function actualizarGeneral() {
                const restante = limiteExamen - Date.now();

                if (restante <= 0) {
                    spanGeneral.textContent = '00:00';
                    entregar();
                    return;
                }

                const minutos = Math.floor(restante / 60000);
                const segundos = Math.floor((restante % 60000) / 1000);
                spanGeneral.textContent = String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
            }

            actualizarGeneral();
            setInterval(actualizarGeneral, 1000);
        })();
    </script>
@endsection
