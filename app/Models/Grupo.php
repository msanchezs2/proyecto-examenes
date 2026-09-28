<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Grupo extends Model
{
    protected $fillable = ['nombre', 'ciclo', 'administrado_por'];

    public function administrador(): BelongsTo
    {
        return $this->belongsTo(Administrador::class, 'administrado_por');
    }

    public function alumnos(): BelongsToMany
    {
        return $this->belongsToMany(Alumno::class, 'alumno_grupo', 'grupo_id', 'user_id');
    }

    public function examenes(): BelongsToMany
    {
        return $this->belongsToMany(Examen::class, 'examen_grupo');
    }
}
