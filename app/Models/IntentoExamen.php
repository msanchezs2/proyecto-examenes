<?php

namespace App\Models;

use App\Enums\EstadoIntento;
use App\Enums\EstadoRespuesta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntentoExamen extends Model
{
    // Eloquent pluralizaría "IntentoExamen" al inglés ("intento_examens") en vez
    // de "intento_examenes".
    protected $table = 'intento_examenes';

    protected $fillable = [
        'alumno_id', 'examen_id', 'inicio', 'fin',
        'estado', 'calificacion_parcial', 'calificacion_final',
        'orden_preguntas', 'orden_opciones',
    ];

    protected function casts(): array
    {
        return [
            'inicio' => 'datetime',
            'fin' => 'datetime',
            'estado' => EstadoIntento::class,
            'orden_preguntas' => 'array',
            'orden_opciones' => 'array',
        ];
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class, 'alumno_id');
    }

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class);
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class);
    }

    /**
     * Mientras queden respuestas PENDIENTE (abiertas sin corregir), el intento
     * queda en CALIFICACION_PARCIAL mostrando solo lo ya calificado; cuando no
     * queda ninguna pendiente, pasa a CALIFICADO con la nota final.
     */
    public function recalcularCalificacion(): void
    {
        // load() y no loadMissing(): quien llama a este método suele haber
        // tocado "respuestas" un instante antes (crear/actualizar filas), así
        // que una copia ya cacheada estaría desactualizada.
        $this->load('respuestas');

        $quedanPendientes = $this->respuestas
            ->contains(fn (Respuesta $respuesta) => $respuesta->estado === EstadoRespuesta::Pendiente);

        if ($quedanPendientes) {
            $this->update([
                'estado' => EstadoIntento::CalificacionParcial,
                'calificacion_parcial' => $this->respuestas
                    ->where('estado', EstadoRespuesta::Calificada)
                    ->sum('puntaje'),
            ]);

            return;
        }

        $this->update([
            'estado' => EstadoIntento::Calificado,
            'calificacion_final' => $this->respuestas->sum('puntaje'),
        ]);
    }
}
