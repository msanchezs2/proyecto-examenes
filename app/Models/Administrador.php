<?php

namespace App\Models;

use App\Enums\RolUsuario;
use Illuminate\Database\Eloquent\Builder;

/**
 * Vista de User acotada a role = administrador (no hay tabla propia: comparte "users").
 */
class Administrador extends User
{
    protected $table = 'users';

    protected static function booted(): void
    {
        static::addGlobalScope('rolAdministrador', function (Builder $query) {
            $query->where('role', RolUsuario::Administrador->value);
        });

        static::creating(function (User $user) {
            $user->role = RolUsuario::Administrador;
        });
    }
}
