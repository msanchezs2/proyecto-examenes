<?php

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GrupoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'ciclo' => ['required', 'string', 'max:255'],
            'alumnos' => ['array'],
            'alumnos.*' => ['integer', Rule::exists('users', 'id')->where('role', RolUsuario::Alumno->value)],
        ];
    }
}
