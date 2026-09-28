@php
    $esEdicion = isset($grupo);
    $seleccionados = $esEdicion ? $grupo->alumnos->pluck('id')->all() : [];
@endphp

<form
    method="POST"
    action="{{ $esEdicion ? route('grupos.update', $grupo) : route('grupos.store') }}"
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

    <div class="fila">
        <label>
            Nombre
            <input type="text" name="nombre" value="{{ old('nombre', $esEdicion ? $grupo->nombre : '') }}" required>
        </label>
        <label>
            Ciclo
            <input type="text" name="ciclo" value="{{ old('ciclo', $esEdicion ? $grupo->ciclo : '') }}" placeholder="2026-A" required>
        </label>
    </div>

    <p class="etiqueta-seccion">Alumnos</p>
    <div class="lista-check">
        @forelse ($alumnos as $alumno)
            <label class="check-item">
                <input type="checkbox" name="alumnos[]" value="{{ $alumno->id }}"
                    @checked(collect(old('alumnos', $seleccionados))->contains($alumno->id))>
                {{ $alumno->name }}
                <span class="dato">{{ $alumno->alumnoPerfil?->matricula }}</span>
            </label>
        @empty
            <p class="vacio">No hay alumnos registrados todavía.</p>
        @endforelse
    </div>

    <div class="acciones">
        <button type="submit">{{ $esEdicion ? 'Guardar cambios' : 'Crear grupo' }}</button>
        <a href="{{ route('grupos.index') }}">Cancelar</a>
    </div>
</form>
