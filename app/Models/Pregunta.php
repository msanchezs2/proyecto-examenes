<?php

namespace App\Models;

use App\Enums\TipoPregunta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pregunta extends Model
{
    protected $fillable = ['enunciado', 'tipo', 'tema', 'tiempo_seg', 'puntaje', 'creado_por'];

    protected function casts(): array
    {
        return ['tipo' => TipoPregunta::class];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(Administrador::class, 'creado_por');
    }

    public function examenes(): BelongsToMany
    {
        return $this->belongsToMany(Examen::class, 'examen_pregunta')
            ->withPivot('puntaje', 'orden')
            ->withTimestamps();
    }

    /** Solo aplica cuando tipo = OpcionMultiple. */
    public function opciones(): HasMany
    {
        return $this->hasMany(Opcion::class);
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class);
    }
}
