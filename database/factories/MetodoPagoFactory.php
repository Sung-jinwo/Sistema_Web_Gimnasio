<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MetodoPagoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'metod_nombre' => $this->faker->randomElement(['Efectivo', 'Tarjeta', 'Yape', 'Plin', 'Transferencia']),
        ];
    }
}
