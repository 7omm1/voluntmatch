<?php

namespace Database\Factories;

use App\Models\Organizacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organizacion>
 */
class OrganizacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->company().' de la comunidad',
            'ruc' => null,
            'sector' => fake()->randomElement(['Educación', 'Medio ambiente', 'Inclusión', 'Salud']),
            'mision' => 'Organización de demostración generada para pruebas.',
            'verificada' => fake()->boolean(),
        ];
    }

    /** Marca la ONG como verificada. */
    public function verificada(): static
    {
        return $this->state(fn () => ['verificada' => true]);
    }
}
