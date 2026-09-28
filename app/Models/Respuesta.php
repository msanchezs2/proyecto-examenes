<?php

namespace App\Models;

use App\Enums\EstadoRespuesta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Respuesta extends Model
{
    protected $fillable = [
        'intento_examen_id', 'pregunta_id', 'opcion_id', 'texto',
        'es_correcta', 'puntaje', 'estado', 'mostrada_en', 'calificado_por', 'calificado_en',
    ];

    protected function casts(): array
    {
        return [
            'es_correcta' => 'boolean',
            'estado' => EstadoRespuesta::class,
            'mostrada_en' => 'datetime',
            'calificado_en' => 'datetime',
        ];
    }

    public function intento(): BelongsTo
    {
        return $this->belongsTo(IntentoExamen::class, 'intento_examen_id');
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class);
    }

    /** Solo se llena si la pregunta es de opción múltiple. */
    public function opcion(): BelongsTo
    {
        return $this->belongsTo(Opcion::class);
    }

    public function calificador(): BelongsTo
    {
        return $this->belongsTo(Administrador::class, 'calificado_por');
    }
}
