<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intento_examenes', function (Blueprint $table) {
            // Veces que el alumno salió de la pestaña/ventana del examen.
            $table->unsignedSmallInteger('salidas_pestana')->default(0)->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('intento_examenes', function (Blueprint $table) {
            $table->dropColumn('salidas_pestana');
        });
    }
};
