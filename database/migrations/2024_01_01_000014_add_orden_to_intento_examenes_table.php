<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intento_examenes', function (Blueprint $table) {
            // Orden aleatorio fijado al iniciar el intento: mismo alumno ve
            // siempre el mismo orden (no se puede "recargar" para reordenar),
            // pero distinto alumno (o distinto intento) ve otro orden.
            $table->json('orden_preguntas')->nullable()->after('estado');
            $table->json('orden_opciones')->nullable()->after('orden_preguntas');
        });
    }

    public function down(): void
    {
        Schema::table('intento_examenes', function (Blueprint $table) {
            $table->dropColumn(['orden_preguntas', 'orden_opciones']);
        });
    }
};
