<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examenes', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->dateTime('apertura');
            $table->dateTime('cierre');
            $table->unsignedInteger('duracion_min');
            $table->unsignedTinyInteger('max_intentos')->default(1);
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examenes');
    }
};
