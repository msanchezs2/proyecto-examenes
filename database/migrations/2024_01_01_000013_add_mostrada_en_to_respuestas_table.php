<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('respuestas', function (Blueprint $table) {
            // momento en que el alumno vio esta pregunta por primera vez; el
            // límite de tiempo_seg de la pregunta se cuenta desde acá, no
            // desde el inicio del examen.
            $table->timestamp('mostrada_en')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('respuestas', function (Blueprint $table) {
            $table->dropColumn('mostrada_en');
        });
    }
};
