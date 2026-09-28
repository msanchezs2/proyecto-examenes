<?php

namespace App\Models;

use App\Enums\RolUsuario;
use Illuminate\Database\Eloquent\Builder;

/**
 * Vista de User acotada a role = alumno (no hay tabla propia: comparte "users").
 */
class Alumno extends User
{
    protected $table = 'users';

    protected static function booted(): void
    {
        static::addGlobalScope('rolAlumno', function (Builder $query) {
            $query->where('role', RolUsuario::Alumno->value);
        });

        static::creating(function (User $user) {
            $user->role = RolUsuario::Alumno;
        });
    }
}
