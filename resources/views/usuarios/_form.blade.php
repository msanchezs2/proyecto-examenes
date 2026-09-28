@php
    $esEdicion = isset($usuario);
    $esUnoMismo = $esEdicion && $usuario->id === auth()->id();
    $rolActual = old('role', $esEdicion ? $usuario->role->value : 'alumno');
@endphp

<form
    method="POST"
    action="{{ $esEdicion ? route('usuarios.update', $usuario) : route('usuarios.store') }}"
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
        Nombre completo
        <input type="text" name="name" value="{{ old('name', $esEdicion ? $usuario->name : '') }}" required>
    </label>

    <label>
        Correo
        <input type="email" name="email" value="{{ old('email', $esEdicion ? $usuario->email : '') }}" required>
    </label>

    <label>
        {{ $esEdicion ? 'Nueva contraseña' : 'Contraseña' }}
        <input type="password" name="password" autocomplete="new-password" minlength="10" @if (! $esEdicion) required @endif>
        <span class="ayuda">
            @if ($esEdicion)
                Dejala en blanco para no cambiarla.
            @endif
            Mínimo 10 caracteres, con mayúsculas, minúsculas y números.
        </span>
    </label>

    <label>
        Rol
        @if ($esUnoMismo)
            <input type="text" value="{{ $rolActual === 'administrador' ? 'Administrador' : 'Alumno' }}" disabled>
            <input type="hidden" name="role" value="{{ $rolActual }}">
            <span class="ayuda">No podés cambiar tu propio rol.</span>
        @else
            <select name="role" id="rol-select" required>
                <option value="alumno" @selected($rolActual === 'alumno')>Alumno</option>
                <option value="administrador" @selected($rolActual === 'administrador')>Administrador</option>
            </select>
        @endif
    </label>

    <div id="seccion-alumno" class="fila" style="{{ $rolActual === 'alumno' ? 'display:flex' : 'display:none' }}">
        <label>
            Matrícula
            <input type="text" name="matricula" value="{{ old('matricula', $esEdicion ? $usuario->alumnoPerfil?->matricula : '') }}">
        </label>
        <label>
            Promedio
            <input type="number" step="0.1" min="0" max="10" name="promedio" value="{{ old('promedio', $esEdicion ? $usuario->alumnoPerfil?->promedio : '') }}">
        </label>
        <label>
            Grupo
            <select name="grupo_id">
                <option value="">Seleccioná un grupo</option>
                @php
                    $grupoActual = old('grupo_id', $esEdicion ? $usuario->grupos->first()?->id : null);
                @endphp
                @foreach ($grupos as $grupo)
                    <option value="{{ $grupo->id }}" @selected((int) $grupoActual === $grupo->id)>{{ $grupo->nombre }} ({{ $grupo->ciclo }})</option>
                @endforeach
            </select>
            <span class="ayuda">Los alumnos ven un examen solo si su grupo está asignado a ese examen.</span>
        </label>
    </div>

    <div class="acciones">
        <button type="submit">{{ $esEdicion ? 'Guardar cambios' : 'Crear usuario' }}</button>
        <a href="{{ route('usuarios.index') }}">Cancelar</a>
    </div>
</form>

<script nonce="{{ Vite::cspNonce() }}">
    (function () {
        var select = document.getElementById('rol-select');
        var seccion = document.getElementById('seccion-alumno');
        if (!select || !seccion) return;

        select.addEventListener('change', function () {
            seccion.style.display = select.value === 'alumno' ? 'flex' : 'none';
        });
    })();
</script>
