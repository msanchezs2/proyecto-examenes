<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumno_grupo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grupo_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'grupo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumno_grupo');
    }
};
