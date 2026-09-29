@php
    $esEdicion = isset($examen);
    $gruposSeleccionados = $esEdicion ? $examen->grupos->pluck('id')->all() : [];
    $preguntasSeleccionadas = $esEdicion ? $examen->preguntas->pluck('id')->all() : [];
@endphp

<form
    method="POST"
    action="{{ $esEdicion ? route('examenes.update', $examen) : route('examenes.store') }}"
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
        Título
        <input type="text" name="titulo" value="{{ old('titulo', $esEdicion ? $examen->titulo : '') }}" required>
    </label>

    <div class="fila">
        <label>
            Apertura <span class="dato">(hora de Ciudad de México)</span>
            <input
                type="datetime-local"
                name="apertura"
                value="{{ old('apertura', $esEdicion ? $examen->apertura->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"
                required
            >
        </label>
        <label>
            Cierre <span class="dato">(hora de Ciudad de México)</span>
            <input
                type="datetime-local"
                name="cierre"
                value="{{ old('cierre', $esEdicion ? $examen->cierre->format('Y-m-d\TH:i') : now()->addWeek()->format('Y-m-d\TH:i')) }}"
                required
            >
        </label>
    </div>

    <div class="fila">
        <label>
            Duración (minutos)
            <input type="number" min="1" name="duracion_min" value="{{ old('duracion_min', $esEdicion ? $examen->duracion_min : 45) }}" required>
        </label>
        <label>
            Intentos permitidos
            <input type="number" min="1" name="max_intentos" value="{{ old('max_intentos', $esEdicion ? $examen->max_intentos : 1) }}" required>
        </label>
    </div>

    <p class="etiqueta-seccion">Grupos asignados</p>
    <div class="lista-check">
        @forelse ($grupos as $grupo)
            <label class="check-item">
                <input type="checkbox" name="grupos[]" value="{{ $grupo->id }}"
                    @checked(collect(old('grupos', $gruposSeleccionados))->contains($grupo->id))>
                {{ $grupo->nombre }}
                <span class="dato">{{ $grupo->ciclo }}</span>
            </label>
        @empty
            <p class="vacio">No hay grupos creados todavía.</p>
        @endforelse
    </div>

    <p class="etiqueta-seccion">Preguntas del banco</p>
    @error('preguntas')
        <p style="color:var(--danger); font-size:13px; margin:-8px 0 10px;">{{ $message }}</p>
    @enderror
    <div class="lista-check">
        @forelse ($preguntas->groupBy('tema') as $tema => $preguntasDelTema)
            <p class="grupo-tema">{{ $tema }}</p>
            @foreach ($preguntasDelTema as $pregunta)
                <label class="check-item">
                    <input type="checkbox" name="preguntas[]" value="{{ $pregunta->id }}"
                        @checked(collect(old('preguntas', $preguntasSeleccionadas))->contains($pregunta->id))>
                    {{ \Illuminate\Support\Str::limit($pregunta->enunciado, 70) }}
                    <span class="dato">{{ $pregunta->puntaje }} pts</span>
                </label>
            @endforeach
        @empty
            <p class="vacio">No hay preguntas en el banco todavía.</p>
        @endforelse
    </div>

    <div class="acciones">
        <button type="submit">{{ $esEdicion ? 'Guardar cambios' : 'Crear examen' }}</button>
        <a href="{{ route('examenes.index') }}">Cancelar</a>
    </div>
</form>
