<?php

namespace App\Models;

use App\Enums\RolUsuario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => RolUsuario::class,
        ];
    }

    /**
     * Invalida "recordarme" y las sesiones guardadas en base de datos de este
     * usuario (solo aplica con SESSION_DRIVER=database). Se usa al cambiar su
     * contraseña para que un acceso robado no siga vivo.
     */
    public function cerrarSesionesActivas(): void
    {
        $this->forceFill(['remember_token' => Str::random(60)])->save();

        DB::table(config('session.table', 'sessions'))->where('user_id', $this->getKey())->delete();
    }

    // --- lado alumno ---

    public function alumnoPerfil(): HasOne
    {
        // FK explícita: Alumno/Administrador son subclases de User y Eloquent
        // adivinaría "alumno_id" a partir del nombre de la subclase si se llama
        // desde un $alumno en vez de un $user.
        return $this->hasOne(AlumnoPerfil::class, 'user_id');
    }

    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'alumno_grupo', 'user_id', 'grupo_id');
    }

    public function intentos(): HasMany
    {
        return $this->hasMany(IntentoExamen::class, 'alumno_id');
    }

    // --- lado administrador ---

    public function gruposAdministrados(): HasMany
    {
        return $this->hasMany(Grupo::class, 'administrado_por');
    }

    public function examenesCreados(): HasMany
    {
        return $this->hasMany(Examen::class, 'creado_por');
    }

    public function preguntasCreadas(): HasMany
    {
        return $this->hasMany(Pregunta::class, 'creado_por');
    }

    public function respuestasCalificadas(): HasMany
    {
        return $this->hasMany(Respuesta::class, 'calificado_por');
    }
}
