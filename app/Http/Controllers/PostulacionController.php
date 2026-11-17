<?php

namespace App\Http\Controllers;

use App\Models\Oportunidad;
use App\Models\Postulacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Postulaciones reales de voluntarios a oportunidades.
 * Solo el rol `voluntario` llega aqui (middleware role:voluntario en routes/web.php).
 */
class PostulacionController extends Controller
{
    public function guardar(Request $request)
    {
        $datos = $request->validate([
            'oportunidad_id' => ['required', 'integer', Rule::exists('oportunidades', 'id')],
        ]);

        $oportunidad = Oportunidad::findOrFail($datos['oportunidad_id']);

        // Idempotente: si ya postuló a este reto, no duplicamos la fila.
        Postulacion::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'oportunidad_id' => $oportunidad->id,
            ],
            [
                'estado' => 'en revision',
            ],
        );

        return back()->with(
            'success',
            "¡Postulación enviada a «{$oportunidad->titulo}»! La organización la revisará pronto."
        );
    }
}
