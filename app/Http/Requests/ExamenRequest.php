<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'apertura' => ['required', 'date'],
            'cierre' => ['required', 'date', 'after:apertura'],
            'duracion_min' => ['required', 'integer', 'min:1'],
            'max_intentos' => ['required', 'integer', 'min:1'],
            'grupos' => ['array'],
            'grupos.*' => ['integer', 'exists:grupos,id'],
            'preguntas' => ['required', 'array', 'min:1'],
            'preguntas.*' => ['integer', 'exists:preguntas,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'preguntas.required' => 'Seleccioná al menos una pregunta del banco.',
            'cierre.after' => 'La fecha de cierre debe ser posterior a la de apertura.',
        ];
    }
}
