<?php

namespace Database\Factories;

use App\Models\Oportunidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Oportunidad>
 */
class OportunidadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'titulo' => 'Proyecto demo '.$this->faker->unique()->numberBetween(1000, 9999),
            'causa' => fake()->randomElement(['Educación', 'Medio ambiente', 'Inclusión', 'Salud']),
            'habilidad' => fake()->randomElement(['UX/UI', 'Docencia', 'Comunicación', 'Datos', 'Logística']),
            'dedicacion' => fake()->randomElement(['2 h/semana', '4 h/semana', '6 h/semana', 'Proyecto puntual']),
            'modalidad' => fake()->randomElement(['Remoto', 'Híbrido', 'Presencial', 'Mixto']),
            'afinidad' => fake()->numberBetween(60, 98),
            'imagen' => null,
            'resumen' => 'Oportunidad de demostración generada para pruebas.',
            'aprenderas' => 'Trabajo con comunidades y gestión de proyectos.',
            'porque' => ['Coincide con tu disponibilidad.', 'Encaja con tus intereses.'],
        ];
    }
}
