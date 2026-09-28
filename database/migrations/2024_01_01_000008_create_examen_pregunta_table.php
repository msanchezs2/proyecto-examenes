<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examen_pregunta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_id')->constrained('examenes')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained()->cascadeOnDelete();
            // overrides opcionales para este examen puntual; null = usa los del banco
            $table->float('puntaje')->nullable();
            $table->unsignedInteger('orden')->nullable();
            $table->timestamps();
            $table->unique(['examen_id', 'pregunta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examen_pregunta');
    }
};
