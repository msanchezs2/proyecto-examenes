@php
    $esEdicion = isset($pregunta);
    $opcionesIniciales = $esEdicion ? $pregunta->opciones : collect();
    $indiceCorrectaInicial = $opcionesIniciales->search(fn ($o) => $o->es_correcta);
    $tipoActual = old('tipo', $esEdicion ? $pregunta->tipo->value : 'opcion_multiple');
@endphp

<form
    method="POST"
    action="{{ $esEdicion ? route('preguntas.update', $pregunta) : route('preguntas.store') }}"
    class="form-pregunta"
>
    @csrf
    @if ($esEdicion)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="errores">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <label>
        Tema
        <input type="text" name="tema" list="temas-existentes" value="{{ old('tema', $esEdicion ? $pregunta->tema : '') }}" required>
        <datalist id="temas-existentes">
            @foreach ($temas as $tema)
                <option value="{{ $tema }}"></option>
            @endforeach
        </datalist>
    </label>

    <label>
        Tipo
        <select name="tipo" id="tipo-select" required>
            <option value="abierta" @selected($tipoActual === 'abierta')>Abierta</option>
            <option value="opcion_multiple" @selected($tipoActual === 'opcion_multiple')>Opción múltiple</option>
        </select>
    </label>

    <label>
        Enunciado
        <textarea name="enunciado" rows="3" required>{{ old('enunciado', $esEdicion ? $pregunta->enunciado : '') }}</textarea>
    </label>

    <div class="fila">
        <label>
            Puntaje
            <input type="number" step="0.5" min="0" name="puntaje" value="{{ old('puntaje', $esEdicion ? $pregunta->puntaje : 10) }}" required>
        </label>
        <label>
            Tiempo límite (segundos)
            <input type="number" min="1" name="tiempo_seg" value="{{ old('tiempo_seg', $esEdicion ? $pregunta->tiempo_seg : 60) }}">
        </label>
    </div>

    <div id="seccion-opciones">
        <p class="ayuda">Marcá con el círculo cuál opción es la correcta. Se necesitan al menos 2.</p>
        <div id="lista-opciones">
            @forelse ($opcionesIniciales as $i => $opcion)
                <div class="fila-opcion">
                    <input type="radio" name="correcta" value="{{ $i }}" @checked((string) old('correcta', $indiceCorrectaInicial) === (string) $i) required>
                    <input type="hidden" name="opciones[{{ $i }}][id]" value="{{ $opcion->id }}">
                    <input type="text" name="opciones[{{ $i }}][texto]" value="{{ old("opciones.$i.texto", $opcion->texto) }}" placeholder="Texto de la opción">
                    <button type="button" class="quitar-opcion">Quitar</button>
                </div>
            @empty
                <div class="fila-opcion">
                    <input type="radio" name="correcta" value="0" checked required>
                    <input type="hidden" name="opciones[0][id]" value="">
                    <input type="text" name="opciones[0][texto]" placeholder="Texto de la opción">
                    <button type="button" class="quitar-opcion">Quitar</button>
                </div>
                <div class="fila-opcion">
                    <input type="radio" name="correcta" value="1">
                    <input type="hidden" name="opciones[1][id]" value="">
                    <input type="text" name="opciones[1][texto]" placeholder="Texto de la opción">
                    <button type="button" class="quitar-opcion">Quitar</button>
                </div>
            @endforelse
        </div>
        <button type="button" id="agregar-opcion">+ Agregar opción</button>
    </div>

    <div class="acciones">
        <button type="submit">{{ $esEdicion ? 'Guardar cambios' : 'Crear pregunta' }}</button>
        <a href="{{ route('preguntas.index') }}">Cancelar</a>
    </div>
</form>

<script nonce="{{ Vite::cspNonce() }}">
(function () {
    const tipoSelect = document.getElementById('tipo-select');
    const seccionOpciones = document.getElementById('seccion-opciones');
    const listaOpciones = document.getElementById('lista-opciones');
    const botonAgregar = document.getElementById('agregar-opcion');
    let contador = {{ max($opcionesIniciales->count(), 2) }};

    function actualizarVisibilidad() {
        const esOpcionMultiple = tipoSelect.value === 'opcion_multiple';
        seccionOpciones.style.display = esOpcionMultiple ? 'block' : 'none';
        // disabled, no solo oculto: un input oculto con CSS igual se manda
        // en el POST, y el backend terminaba exigiendo texto de opciones
        // aunque la pregunta fuera de tipo "abierta".
        listaOpciones.querySelectorAll('input').forEach((el) => {
            el.disabled = !esOpcionMultiple;
        });
        listaOpciones.querySelectorAll('input[name="correcta"]').forEach((el) => {
            el.required = esOpcionMultiple;
        });
    }
    tipoSelect.addEventListener('change', actualizarVisibilidad);
    actualizarVisibilidad();

    botonAgregar.addEventListener('click', function () {
        const i = contador++;
        const fila = document.createElement('div');
        fila.className = 'fila-opcion';
        fila.innerHTML = `
            <input type="radio" name="correcta" value="${i}">
            <input type="hidden" name="opciones[${i}][id]" value="">
            <input type="text" name="opciones[${i}][texto]" placeholder="Texto de la opción">
            <button type="button" class="quitar-opcion">Quitar</button>
        `;
        listaOpciones.appendChild(fila);
    });

    listaOpciones.addEventListener('click', function (e) {
        if (e.target.classList.contains('quitar-opcion')) {
            e.target.closest('.fila-opcion').remove();
        }
    });
})();
</script>
