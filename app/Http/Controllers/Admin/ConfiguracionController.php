<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Configuracion;
use Illuminate\Http\Request;

/**
 * Configuracion de la plataforma: pesos del algoritmo de matching y
 * reglas de moderacion, guardadas en la tabla `configuraciones`.
 */
class ConfiguracionController extends Controller
{
    public const PESOS_POR_DEFECTO = [
        'habilidades' => 70,
        'horario' => 55,
        'causa' => 80,
        'cercania' => 35,
    ];

    public const REGLAS_POR_DEFECTO = [
        'aviso' => '',
        'max_postulaciones' => 5,
        'ocultar_reportadas' => false,
    ];

    public function index()
    {
        return view('admin.configuracion.index', [
            'pesos' => Configuracion::obtener('matching.pesos', self::PESOS_POR_DEFECTO),
            'reglas' => Configuracion::obtener('moderacion', self::REGLAS_POR_DEFECTO),
            'actualizado' => Configuracion::query()->max('updated_at'),
        ]);
    }

    public function update(Request $request)
    {
        $datos = $request->validate([
            'habilidades' => 'required|integer|min:0|max:100',
            'horario' => 'required|integer|min:0|max:100',
            'causa' => 'required|integer|min:0|max:100',
            'cercania' => 'required|integer|min:0|max:100',
            'aviso' => 'nullable|string|max:500',
            'max_postulaciones' => 'required|integer|min:1|max:50',
            'ocultar_reportadas' => 'nullable|boolean',
        ]);

        $pesos = [
            'habilidades' => (int) $datos['habilidades'],
            'horario' => (int) $datos['horario'],
            'causa' => (int) $datos['causa'],
            'cercania' => (int) $datos['cercania'],
        ];

        $reglas = [
            'aviso' => $datos['aviso'] ?? '',
            'max_postulaciones' => (int) $datos['max_postulaciones'],
            'ocultar_reportadas' => $request->boolean('ocultar_reportadas'),
        ];

        Configuracion::guardar('matching.pesos', $pesos);
        Configuracion::guardar('moderacion', $reglas);

        AuditLog::registrar('configuracion.actualizada', null, json_encode(['pesos' => $pesos, 'reglas' => $reglas]));

        return back()->with('status', 'Configuración guardada. Se aplicará en las próximas recomendaciones.');
    }
}
