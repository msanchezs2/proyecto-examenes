<?php

namespace App\Http\Requests;

use App\Enums\TipoPregunta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PreguntaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tema' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::enum(TipoPregunta::class)],
            'enunciado' => ['required', 'string'],
            'tiempo_seg' => ['nullable', 'integer', 'min:1'],
            'puntaje' => ['required', 'numeric', 'min:0'],
            'opciones' => ['required_if:tipo,'.TipoPregunta::OpcionMultiple->value, 'array'],
            // nullable: el middleware ConvertEmptyStringsToNull vuelve null los
            // campos de opciones que llegan vacíos en una pregunta "abierta";
            // sin esto, 'string' los rechazaba aunque no sean obligatorios acá.
            'opciones.*.texto' => ['nullable', 'required_if:tipo,'.TipoPregunta::OpcionMultiple->value, 'string', 'max:255'],
            'correcta' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('tipo') !== TipoPregunta::OpcionMultiple->value) {
                return;
            }

            $opciones = collect($this->input('opciones', []))
                ->filter(fn ($opcion) => filled($opcion['texto'] ?? null));

            if ($opciones->count() < 2) {
                $validator->errors()->add('opciones', 'Una pregunta de opción múltiple necesita al menos 2 opciones con texto.');
            }

            if (! $opciones->has((string) $this->input('correcta'))) {
                $validator->errors()->add('correcta', 'Elegí cuál opción es la correcta.');
            }
        });
    }
}
