<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cliente>
 */
class ClienteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'data_nascimento' => fake()->dateTimeBetween('-70 years', '-5 years')->format('Y-m-d'),
            'foto' => null,
            'observacoes' => fake()->boolean(30) ? fake()->sentence() : null,
            'creditos' => fake()->randomFloat(2, 0, 500),
        ];
    }

    /**
     * Cliente sem créditos (usado para testar o badge "créditos zerados").
     */
    public function semCreditos(): static
    {
        return $this->state(fn (array $attributes) => [
            'creditos' => 0,
        ]);
    }
}
