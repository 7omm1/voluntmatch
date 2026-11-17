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
        Schema::create('postulaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('oportunidad_id')->constrained('oportunidades')->cascadeOnDelete();
            $table->string('estado')->default('en revision'); // en revision | en entrevista | aceptado | rechazado
            $table->text('nota')->nullable();                 // mensaje del voluntario al postularse
            $table->timestamps();

            $table->unique(['user_id', 'oportunidad_id']); // una sola postulacion por voluntario y oportunidad
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postulaciones');
    }
};
