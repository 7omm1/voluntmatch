<?php

namespace App\Http\Controllers;

use App\Support\PerfilVoluntario;
use App\Support\Peru;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PerfilController extends Controller
{
    /** Completar o editar los datos del perfil del estudiante. */
    public function actualizar(Request $request)
    {
        $user = $request->user();

        $datos = $request->validate([
            'name' => 'required|string|max:255',
            'dni' => ['nullable', 'digits:8', Rule::unique('users', 'dni')->ignore($user->id)],
            'phone' => 'nullable|string|regex:/^9\d{8}$/',
            'region' => ['nullable', Rule::in(Peru::REGIONES)],
            'city' => 'nullable|string|max:100',
            'university' => 'nullable|string|max:150',
            'career' => 'nullable|string|max:150',
            'cycle' => ['nullable', Rule::in(Peru::CICLOS)],
            'availability' => ['nullable', Rule::in(array_keys(Peru::DISPONIBILIDADES))],
            'interests' => 'nullable|array|max:6',
            'interests.*' => ['string', Rule::in(Peru::INTERESES)],
        ], [
            'dni.digits' => 'El DNI debe tener exactamente 8 dígitos.',
            'dni.unique' => 'Ese DNI ya está registrado en otra cuenta.',
            'phone.regex' => 'El celular debe tener 9 dígitos y empezar con 9 (ej. 987654321).',
            'region.in' => 'Selecciona una región válida del Perú.',
            'interests.max' => 'Puedes elegir hasta 6 causas.',
        ]);

        $datos['interests'] = array_values($request->input('interests', []));

        $user->fill($datos)->save();

        return $this->respuesta($request, 'Perfil actualizado. Revisa tu porcentaje de completitud.');
    }

    /** Guardar el Skills Passport (fortalezas) del estudiante. */
    public function skills(Request $request)
    {
        $request->validate([
            'skills' => 'required|array|min:1',
            'skills.*' => 'integer|min:20|max:100',
        ], [
            'skills.required' => 'Ajusta al menos una fortaleza.',
            'skills.*.integer' => 'Cada fortaleza debe ser un porcentaje.',
            'skills.*.min' => 'El porcentaje mínimo es 20%.',
            'skills.*.max' => 'El porcentaje máximo es 100%.',
        ]);

        $skills = [];
        foreach (PerfilVoluntario::FORTALEZAS as $fortaleza) {
            $valor = $request->input("skills.$fortaleza");
            if (is_numeric($valor)) {
                $skills[$fortaleza] = (int) $valor;
            }
        }

        $user = $request->user();
        $user->skills = $skills;
        $user->save();

        return $this->respuesta($request, 'Skills Passport actualizado.');
    }

    /** Respuesta para fetch (JSON) o para un envío normal del formulario. */
    private function respuesta(Request $request, string $mensaje)
    {
        $user = $request->user();

        $payload = [
            'ok' => true,
            'mensaje' => $mensaje,
            'porcentaje' => PerfilVoluntario::porcentaje($user),
            'puedeHacerMatch' => PerfilVoluntario::puedeHacerMatch($user),
            'faltantes' => array_map(fn ($item) => $item['label'], PerfilVoluntario::faltantes($user)),
            'destacadas' => PerfilVoluntario::destacadas($user),
        ];

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return back()->with('success', $mensaje);
    }
}