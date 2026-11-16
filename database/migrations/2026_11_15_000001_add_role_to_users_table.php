<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
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

        // Cuenta administradora del ecosistema VoluntMatch.
        \App\Models\User::create([
            'name' => 'Administración VoluntMatch',
            'email' => 'admin@voluntmatch.org',
            'password' => Hash::make('REDACTED'),
            'role' => 'admin',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        \App\Models\User::where('email', 'admin@voluntmatch.org')->delete();
    }
};
