<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preguntas', function (Blueprint $table) {
            $table->id();
            $table->text('enunciado');
            $table->string('tipo', 20); // TipoPregunta: abierta | opcion_multiple
            $table->string('tema')->nullable();
            $table->unsignedInteger('tiempo_seg')->nullable();
            $table->float('puntaje')->default(1);
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preguntas');
    }
};
