<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Cuenta administradora: credenciales desde .env (ADMIN_EMAIL / ADMIN_PASSWORD).
        $this->call(AdminSeeder::class);

        // Catalogo de oportunidades demo en la tabla real `oportunidades`.
        $this->call(OportunidadSeeder::class);

        // Varias ONG y estudiantes con perfiles y postulaciones reales.
        $this->call(ComunidadDemoSeeder::class);

        // Red de ONG del Cusco para el directorio publico y el mapa.
        $this->call(DirectorioCuscoSeeder::class);

        // Datos demo para los modulos del panel de administracion.
        $this->call(AdminDemoSeeder::class);

        // Corpus del asistente virtual (RAG) y fragmentos indexados.
        $this->call(ChatbotKnowledgeSeeder::class);
    }
}
