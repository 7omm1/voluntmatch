<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('oportunidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $table->string('titulo');
            $table->string('causa');
            $table->string('habilidad');
            $table->string('dedicacion');          // "4 h/semana", "Proyecto puntual", etc.
            $table->string('modalidad');           // Remoto, Híbrido, Presencial, Mixto
            $table->unsignedTinyInteger('afinidad')->nullable(); // % de match calculado (0-100)
            $table->string('imagen')->nullable();
            $table->text('resumen');
            $table->text('aprenderas')->nullable();
            $table->json('porque')->nullable();    // razones del match, en orden
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oportunidades');
    }
};
