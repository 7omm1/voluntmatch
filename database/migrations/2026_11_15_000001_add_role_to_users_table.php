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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('voluntario')->after('password');
        });

        // La cuenta administradora no se crea aqui: lo hace
        // database/seeders/AdminSeeder.php con credenciales del .env
        // (ADMIN_EMAIL y ADMIN_PASSWORD), nunca con una contrasenia fija.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
