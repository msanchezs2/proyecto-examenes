<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opcion extends Model
{
    // Eloquent pluralizaría "Opcion" al inglés ("opcions") en vez de "opciones".
    protected $table = 'opciones';

    protected $fillable = ['pregunta_id', 'texto', 'es_correcta'];

    protected function casts(): array
    {
        return ['es_correcta' => 'boolean'];
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class);
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class);
    }
}
