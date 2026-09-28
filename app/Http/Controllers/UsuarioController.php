<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Http\Requests\UsuarioRequest;
use App\Models\Grupo;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $rol = $request->string('rol')->toString();

        $usuarios = User::query()
            ->with('alumnoPerfil')
            ->when($rol !== '', fn ($query) => $query->where('role', $rol))
            ->orderBy('name')
            ->get();

        return view('usuarios.index', [
            'usuarios' => $usuarios,
            'rolSeleccionado' => $rol,
        ]);
    }

    public function create(): View
    {
        return view('usuarios.create', [
            'grupos' => Grupo::orderBy('nombre')->get(),
        ]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        $usuario = User::create($request->safe()->except(['matricula', 'promedio', 'grupo_id']));

        if ($usuario->role === RolUsuario::Alumno) {
            $usuario->alumnoPerfil()->create([
                'matricula' => $request->input('matricula'),
                'promedio' => $request->input('promedio'),
            ]);

            $usuario->grupos()->sync([$request->input('grupo_id')]);
        }

        return redirect()->route('usuarios.index')->with('status', 'Usuario creado.');
    }

    public function edit(User $usuario): View
    {
        $usuario->load('alumnoPerfil', 'grupos');

        return view('usuarios.edit', [
            'usuario' => $usuario,
            'grupos' => Grupo::orderBy('nombre')->get(),
        ]);
    }

    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        $datos = $request->safe()->except(['matricula', 'promedio', 'grupo_id', 'password']);

        if ($request->filled('password')) {
            $datos['password'] = $request->input('password');
        }

        if ($usuario->id === auth()->id()) {
            unset($datos['role']);
        }

        $usuario->update($datos);

        if ($request->filled('password') && $usuario->id !== auth()->id()) {
            $usuario->cerrarSesionesActivas();
        }

        if ($usuario->role === RolUsuario::Alumno) {
            $usuario->alumnoPerfil()->updateOrCreate([], [
                'matricula' => $request->input('matricula'),
                'promedio' => $request->input('promedio'),
            ]);

            $usuario->grupos()->sync([$request->input('grupo_id')]);
        } else {
            $usuario->alumnoPerfil()->delete();
            $usuario->grupos()->sync([]);
        }

        return redirect()->route('usuarios.index')->with('status', 'Usuario actualizado.');
    }

    public function destroy(User $usuario): RedirectResponse
    {
        abort_if($usuario->id === auth()->id(), 403, 'No podés eliminar tu propia cuenta.');

        $tieneRelaciones = $usuario->gruposAdministrados()->exists()
            || $usuario->examenesCreados()->exists()
            || $usuario->preguntasCreadas()->exists()
            || $usuario->respuestasCalificadas()->exists()
            || $usuario->intentos()->exists();

        if ($tieneRelaciones) {
            return back()->with('error', 'No se puede eliminar: tiene grupos, exámenes, preguntas, calificaciones o intentos asociados.');
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')->with('status', 'Usuario eliminado.');
    }
}
