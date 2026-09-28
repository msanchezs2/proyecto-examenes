<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('respuestas', function (Blueprint $table) {
            // una sola Respuesta por pregunta dentro de un mismo intento: hace
            // seguro el updateOrCreate() del autoguardado incremental.
            $table->unique(['intento_examen_id', 'pregunta_id']);
        });
    }

    public function down(): void
    {
        Schema::table('respuestas', function (Blueprint $table) {
            $table->dropUnique(['intento_examen_id', 'pregunta_id']);
        });
    }
};
