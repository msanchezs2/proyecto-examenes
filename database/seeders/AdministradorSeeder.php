<?php

namespace Database\Seeders;

use App\Models\Administrador;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdministradorSeeder extends Seeder
{
    /**
     * Toma el correo/contraseña del admin inicial de ADMIN_EMAIL y
     * ADMIN_PASSWORD en .env: así no queda una credencial real hardcodeada
     * en el código (y en el historial de git de cada entorno que lo corra).
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');

        if (! $email) {
            $this->command?->warn('ADMIN_EMAIL no está definida en .env: se omite la creación del administrador inicial.');

            return;
        }

        $password = env('ADMIN_PASSWORD');

        if (! $password) {
            $password = Str::password(16);
            $this->command?->warn("ADMIN_PASSWORD no está definida: se generó una contraseña temporal para {$email}: {$password}");
        }

        Administrador::firstOrCreate(
            ['email' => $email],
            ['name' => env('ADMIN_NAME', 'Administrador'), 'password' => Hash::make($password)]
        );
    }
}
