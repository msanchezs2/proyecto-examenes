<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Examen extends Model
{
    // Eloquent pluralizaría "Examen" al inglés ("examens") en vez de "examenes".
    protected $table = 'examenes';

    protected $fillable = ['titulo', 'apertura', 'cierre', 'duracion_min', 'max_intentos', 'creado_por'];

    protected function casts(): array
    {
        return [
            'apertura' => 'datetime',
            'cierre' => 'datetime',
        ];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(Administrador::class, 'creado_por');
    }

    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'examen_grupo');
    }

    /**
     * Preguntas del banco incluidas en este examen.
     * puntaje/orden en el pivot permiten sobrescribir los del banco solo para este examen.
     */
    public function preguntas(): BelongsToMany
    {
        return $this->belongsToMany(Pregunta::class, 'examen_pregunta')
            ->withPivot('puntaje', 'orden')
            ->withTimestamps();
    }

    public function intentos(): HasMany
    {
        return $this->hasMany(IntentoExamen::class);
    }
}
