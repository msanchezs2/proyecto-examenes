<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examen_grupo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_id')->constrained('examenes')->cascadeOnDelete();
            $table->foreignId('grupo_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['examen_id', 'grupo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examen_grupo');
    }
};
