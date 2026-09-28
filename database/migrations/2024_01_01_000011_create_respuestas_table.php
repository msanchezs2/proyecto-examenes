<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intento_examen_id')->constrained('intento_examenes')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained()->restrictOnDelete();
            $table->foreignId('opcion_id')->nullable()->constrained('opciones')->nullOnDelete();
            $table->text('texto')->nullable();
            $table->boolean('es_correcta')->nullable();
            $table->float('puntaje')->nullable();
            $table->string('estado', 20)->default('pendiente'); // EstadoRespuesta
            $table->foreignId('calificado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('calificado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas');
    }
};
