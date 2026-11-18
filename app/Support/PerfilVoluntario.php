<?php

namespace App\Support;

use App\Models\User;

/**
 * Perfil del estudiante: porcentaje de completitud, checklist y
 * requisitos que se piden antes de poder hacer match con una ONG.
 */
class PerfilVoluntario
{
    /** Fortalezas del Skills Passport (el orden es el de los ejes del gráfico). */
    public const FORTALEZAS = [
        'Comunicación',
        'Creatividad',
        'Liderazgo',
        'Análisis',
        'Trabajo en equipo',
    ];

    /**
     * Checklist ponderado del perfil.
     *
     * @return array<int, array{key: string, label: string, done: bool, peso: int, donde: string, clave: bool}>
     */
    public static function checklist(?User $user): array
    {
        $intereses = is_array($user?->interests) ? $user->interests : [];
        $skills = is_array($user?->skills) ? $user->skills : [];

        $items = [
            ['key' => 'name', 'label' => 'Nombre completo', 'done' => filled($user?->name), 'peso' => 5, 'donde' => 'cuenta', 'clave' => false],
            ['key' => 'email', 'label' => 'Correo electrónico', 'done' => filled($user?->email), 'peso' => 5, 'donde' => 'cuenta', 'clave' => false],
            ['key' => 'dni', 'label' => 'DNI', 'done' => filled($user?->dni), 'peso' => 10, 'donde' => 'datos', 'clave' => true],
            ['key' => 'phone', 'label' => 'Celular de contacto', 'done' => filled($user?->phone), 'peso' => 10, 'donde' => 'datos', 'clave' => true],
            ['key' => 'region', 'label' => 'Región', 'done' => filled($user?->region), 'peso' => 5, 'donde' => 'datos', 'clave' => true],
            ['key' => 'city', 'label' => 'Ciudad o distrito', 'done' => filled($user?->city), 'peso' => 5, 'donde' => 'datos', 'clave' => false],
            ['key' => 'university', 'label' => 'Universidad o instituto', 'done' => filled($user?->university), 'peso' => 10, 'donde' => 'datos', 'clave' => true],
            ['key' => 'career', 'label' => 'Carrera profesional', 'done' => filled($user?->career), 'peso' => 10, 'donde' => 'datos', 'clave' => true],
            ['key' => 'cycle', 'label' => 'Ciclo o grado', 'done' => filled($user?->cycle), 'peso' => 5, 'donde' => 'datos', 'clave' => false],
            ['key' => 'availability', 'label' => 'Disponibilidad semanal', 'done' => filled($user?->availability), 'peso' => 10, 'donde' => 'datos', 'clave' => true],
            ['key' => 'interests', 'label' => 'Al menos 2 causas de interés', 'done' => count($intereses) >= 2, 'peso' => 10, 'donde' => 'intereses', 'clave' => true],
            ['key' => 'skills', 'label' => 'Skills Passport ajustado', 'done' => count($skills) >= 3, 'peso' => 15, 'donde' => 'passport', 'clave' => false],
        ];

        return $items;
    }

    /** Porcentaje ponderado de perfil completado (0-100). */
    public static function porcentaje(?User $user): int
    {
        $puntos = self::puntos($user);

        return $puntos['total'] > 0 ? (int) round($puntos['logrado'] / $puntos['total'] * 100) : 0;
    }

    /**
     * Puntos del perfil. El Skills Passport aporta de forma progresiva:
     * cada punto de fortaleza suma al porcentaje aunque aun no se haya
     * guardado, para que el mapa de fortalezas se sienta vivo.
     *
     * @return array{logrado: float, base: float, total: int, skillsPeso: int, skillsLogrado: float}
     */
    public static function puntos(?User $user, ?array $skills = null): array
    {
        $items = self::checklist($user);
        $skills = $skills ?? self::skills($user);
        $media = count($skills) > 0 ? array_sum($skills) / count($skills) : 0;

        $logrado = 0.0;
        $base = 0.0;
        $total = 0;
        $skillsPeso = 0;

        foreach ($items as $item) {
            $total += $item['peso'];

            if ($item['key'] === 'skills') {
                $skillsPeso = $item['peso'];

                continue;
            }

            if ($item['done']) {
                $logrado += $item['peso'];
                $base += $item['peso'];
            }
        }

        $skillsLogrado = $skillsPeso * ($media / 100);

        return [
            'logrado' => $logrado + $skillsLogrado,
            'base' => $base,
            'total' => $total,
            'skillsPeso' => $skillsPeso,
            'skillsLogrado' => $skillsLogrado,
        ];
    }

    /**
     * Datos que faltan para poder hacer match con una ONG.
     *
     * @return array<int, array{key: string, label: string, done: bool, peso: int, donde: string, clave: bool}>
     */
    public static function faltantes(?User $user): array
    {
        return array_values(array_filter(self::checklist($user), fn ($item) => ! $item['done']));
    }

    /** Datos obligatorios (los "clave") que todavía faltan. */
    public static function faltantesClave(?User $user): array
    {
        return array_values(array_filter(
            self::checklist($user),
            fn ($item) => ! $item['done'] && $item['clave'],
        ));
    }

    /** Un estudiante puede hacer match cuando completó todos los datos clave. */
    public static function puedeHacerMatch(?User $user): bool
    {
        return count(self::faltantesClave($user)) === 0;
    }

    /** Fortalezas guardadas, con valores por defecto razonables. */
    public static function skills(?User $user): array
    {
        $guardadas = is_array($user?->skills) ? $user->skills : [];
        $skills = [];

        foreach (self::FORTALEZAS as $index => $fortaleza) {
            $valor = $guardadas[$fortaleza] ?? null;
            $skills[$fortaleza] = is_numeric($valor)
                ? max(20, min(100, (int) $valor))
                : [88, 82, 67, 74, 92][$index];
        }

        return $skills;
    }

    /** Las dos fortalezas más altas (para el texto "destacas en..."). */
    public static function destacadas(?User $user): array
    {
        $skills = self::skills($user);
        arsort($skills);

        return array_slice(array_keys($skills), 0, 2);
    }
}