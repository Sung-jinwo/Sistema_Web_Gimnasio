<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MembresiaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mem_nombre' => $this->faker->randomElement(['Mensual', 'Trimestral', 'Semestral', 'Anual']) . ' ' . $this->faker->word,
            'mem_precio' => $this->faker->randomFloat(2, 50, 1000),
            'mem_duracion' => $this->faker->randomElement([30, 90, 180, 365]),
            'mem_categoria' => $this->faker->randomElement(['Regular', 'Premium', 'VIP']),
            'mem_tipo' => $this->faker->randomElement(['Mensual', 'Trimestral', 'Semestral', 'Anual']),
            'comision' => $this->faker->randomFloat(2, 5, 20),
            'modalidad' => 'por_meses',
            'estado' => 'A',
        ];
    }
}
