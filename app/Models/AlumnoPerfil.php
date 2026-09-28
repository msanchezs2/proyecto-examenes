<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlumnoPerfil extends Model
{
    // nombre explícito: por defecto Eloquent pluralizaría "AlumnoPerfil" al inglés
    // ("alumno_perfils") en vez de "alumno_perfiles".
    protected $table = 'alumno_perfiles';

    protected $fillable = ['user_id', 'matricula', 'promedio'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
