@extends('layouts.simple')

@section('titulo', 'Usuarios')

@section('content')
    <div class="cabecera">
        <div>
            <h1>Usuarios</h1>
            <p class="subtitle">Alumnos y administradores del sistema.</p>
        </div>
        <a class="boton" href="{{ route('usuarios.create') }}">+ Nuevo usuario</a>
    </div>

    <form method="GET" class="form-filtros">
        <select name="rol" data-auto-enviar>
            <option value="">Todos los roles</option>
            <option value="alumno" @selected($rolSeleccionado === 'alumno')>Alumnos</option>
            <option value="administrador" @selected($rolSeleccionado === 'administrador')>Administradores</option>
        </select>
    </form>

    <p class="contador">{{ $usuarios->count() }} usuario(s)</p>

    @if ($usuarios->isEmpty())
        <p class="vacio">No hay usuarios para este filtro.</p>
    @else
        <div class="tabla-wrap">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Matrícula</th>
                        <th>Promedio</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usuarios as $usuario)
                        <tr>
                            <td>{{ $usuario->name }} @if ($usuario->id === auth()->id()) <span class="dato">(vos)</span> @endif</td>
                            <td>{{ $usuario->email }}</td>
                            <td>
                                <span class="badge {{ $usuario->role->value === 'administrador' ? 'badge-opcion_multiple' : 'badge-tema' }}">
                                    {{ $usuario->role->value === 'administrador' ? 'Administrador' : 'Alumno' }}
                                </span>
                            </td>
                            <td>{{ $usuario->alumnoPerfil?->matricula ?? '—' }}</td>
                            <td>{{ $usuario->alumnoPerfil?->promedio ?? '—' }}</td>
                            <td class="col-acciones">
                                <a class="boton secundario" href="{{ route('usuarios.edit', $usuario) }}">Editar</a>
                                @if ($usuario->id !== auth()->id())
                                    <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" data-confirmar="¿Eliminar este usuario?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="boton secundario">Eliminar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
