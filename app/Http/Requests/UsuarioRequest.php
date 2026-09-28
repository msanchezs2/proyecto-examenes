<?php

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'password' => [$usuario ? 'nullable' : 'required', 'string', Password::defaults()],
            'role' => ['required', Rule::enum(RolUsuario::class)],
            'matricula' => [
                'required_if:role,'.RolUsuario::Alumno->value,
                'nullable',
                'string',
                'max:50',
                Rule::unique('alumno_perfiles', 'matricula')->ignore($usuario?->alumnoPerfil?->id),
            ],
            'promedio' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'grupo_id' => [
                'required_if:role,'.RolUsuario::Alumno->value,
                'nullable',
                'integer',
                Rule::exists('grupos', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'matricula.required_if' => 'La matrícula es obligatoria para un alumno.',
            'grupo_id.required_if' => 'El grupo es obligatorio para un alumno.',
        ];
    }
}
