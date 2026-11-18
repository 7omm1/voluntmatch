<?php

namespace Database\Seeders;

use App\Models\Anuncio;
use App\Models\Oportunidad;
use App\Models\Organizacion;
use App\Models\Postulacion;
use App\Models\Reporte;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Datos de demostracion para los modulos administrativos: fichas de ONG
 * pendientes de verificar, postulaciones, reportes, casos de soporte y
 * anuncios. Es idempotente: solo crea lo que falta.
 */
class AdminDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->vincularOrganizaciones();
        $this->crearPostulaciones();
        $this->crearReportes();
        $this->crearTickets();
        $this->crearAnuncios();
    }

    /** Cada cuenta ONG debe tener su ficha para poder verificarla. */
    private function vincularOrganizaciones(): void
    {
        User::where('role', 'organizacion')
            ->whereDoesntHave('organizacion')
            ->each(function (User $usuario) {
                $usuario->organizacion()->create([
                    'nombre' => $usuario->name,
                    'slug' => Str::slug($usuario->name),
                    'ruc' => $usuario->ruc,
                    'sector' => $usuario->sector,
                    'mision' => $usuario->mission,
                    'verificada' => false,
                ]);
            });
    }

    /** Postulaciones de ejemplo para alimentar métricas y conversión. */
    private function crearPostulaciones(): void
    {
        if (Postulacion::exists()) {
            return;
        }

        $voluntarios = User::where('role', 'voluntario')->take(4)->get();
        $oportunidades = Oportunidad::take(3)->get();

        if ($voluntarios->isEmpty() || $oportunidades->isEmpty()) {
            return;
        }

        $estados = ['aceptado', 'en entrevista', 'en revision', 'rechazado'];

        foreach ($voluntarios as $indice => $voluntario) {
            $oportunidad = $oportunidades[$indice % $oportunidades->count()];

            Postulacion::create([
                'user_id' => $voluntario->id,
                'oportunidad_id' => $oportunidad->id,
                'estado' => $estados[$indice % count($estados)],
                'nota' => 'Me interesa aportar con mi tiempo y aprender del equipo.',
            ]);
        }
    }

    private function crearReportes(): void
    {
        if (Reporte::exists()) {
            return;
        }

        $voluntarios = User::where('role', 'voluntario')->get();
        $organizacion = Organizacion::first();
        $oportunidad = Oportunidad::first();
        $admin = User::where('role', 'admin')->first();

        if ($voluntarios->isEmpty() || ! $admin) {
            return;
        }

        Reporte::create([
            'user_id' => $voluntarios->first()->id,
            'reportado_por' => $voluntarios->last()->id,
            'motivo' => 'Perfil con información falsa',
            'detalle' => 'El perfil declara una universidad que no coincide con su DNI declarado.',
            'estado' => 'pendiente',
        ]);

        if ($oportunidad) {
            Reporte::create([
                'oportunidad_id' => $oportunidad->id,
                'reportado_por' => $voluntarios->first()->id,
                'motivo' => 'Oportunidad duplicada',
                'detalle' => 'La misma convocatoria aparece publicada dos veces con distinto título.',
                'estado' => 'pendiente',
            ]);
        }

        if ($organizacion) {
            Reporte::create([
                'user_id' => $organizacion->user_id,
                'reportado_por' => $voluntarios->first()->id,
                'motivo' => 'No respondió a los postulantes',
                'detalle' => 'La organización no contestó en más de tres semanas.',
                'estado' => 'resuelto',
                'resolucion' => 'Se contactó a la organización y actualizó sus procesos de respuesta.',
                'resuelto_por' => $admin->id,
            ]);
        }
    }

    private function crearTickets(): void
    {
        if (Ticket::exists()) {
            return;
        }

        $voluntarios = User::where('role', 'voluntario')->get();
        $organizacion = User::where('role', 'organizacion')->first();

        Ticket::create([
            'user_id' => $voluntarios->first()?->id,
            'asunto' => 'No puedo registrar mis horas de voluntariado',
            'mensaje' => 'Al guardar el registro de horas aparece un error y no se actualiza mi perfil.',
            'prioridad' => 'alta',
            'estado' => 'abierto',
        ]);

        Ticket::create([
            'user_id' => $organizacion?->id,
            'asunto' => 'Error al adjuntar la ficha RUC',
            'mensaje' => 'El archivo PDF pesa menos de 2 MB pero la plataforma lo rechaza.',
            'prioridad' => 'media',
            'estado' => 'en_proceso',
        ]);

        Ticket::create([
            'user_id' => $voluntarios->last()?->id,
            'asunto' => 'Duda sobre la verificación de mi cuenta',
            'mensaje' => 'Quisiera saber cuánto demora la verificación de mi perfil.',
            'prioridad' => 'baja',
            'estado' => 'cerrado',
            'respuesta' => 'La verificación se completó correctamente. ¡Gracias por escribirnos!',
        ]);
    }

    private function crearAnuncios(): void
    {
        if (Anuncio::exists()) {
            return;
        }

        $admin = User::where('role', 'admin')->first();

        Anuncio::create([
            'titulo' => 'Nueva convocatoria de voluntariado',
            'cuerpo' => 'Esta semana se abren nuevos retos de educación y medio ambiente. Revisa el catálogo y postula al que mejor combine con tu talento.',
            'audiencia' => 'todos',
            'activo' => true,
            'created_by' => $admin?->id,
        ]);

        Anuncio::create([
            'titulo' => 'Recuerda completar tu perfil',
            'cuerpo' => 'Un perfil completo mejora tus recomendaciones: agrega tus habilidades, disponibilidad y universidad.',
            'audiencia' => 'voluntario',
            'activo' => true,
            'created_by' => $admin?->id,
        ]);
    }
}
