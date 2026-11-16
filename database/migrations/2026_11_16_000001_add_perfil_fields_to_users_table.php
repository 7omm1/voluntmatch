<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos de perfil para estudiantes y organizaciones peruanas.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Datos comunes a ambos tipos de cuenta.
            $table->string('phone', 15)->nullable();
            $table->string('region', 60)->nullable();
            $table->string('city', 100)->nullable();

            // Estudiante / voluntario.
            $table->string('dni', 8)->nullable()->unique();
            $table->string('university', 150)->nullable();
            $table->string('career', 150)->nullable();
            $table->string('cycle', 20)->nullable();
            $table->string('availability', 10)->nullable();
            $table->json('interests')->nullable();

            // Organización / ONG.
            $table->string('ruc', 11)->nullable()->unique();
            $table->string('org_type', 60)->nullable();
            $table->string('legal_rep', 150)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('sector', 100)->nullable();
            $table->text('mission')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'region', 'city', 'dni', 'university', 'career', 'cycle',
                'availability', 'interests', 'ruc', 'org_type', 'legal_rep',
                'address', 'website', 'sector', 'mission',
            ]);
        });
    }
};