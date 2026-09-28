<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intento_examenes', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete en ambos: el historial de intentos no debe desaparecer
            // si se borra el alumno o el examen; hay que archivar antes.
            $table->foreignId('alumno_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('examen_id')->constrained('examenes')->restrictOnDelete();
            $table->dateTime('inicio');
            $table->dateTime('fin')->nullable();
            $table->string('estado', 25)->default('en_curso'); // EstadoIntento
            $table->float('calificacion_parcial')->nullable();
            $table->float('calificacion_final')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intento_examenes');
    }
};
