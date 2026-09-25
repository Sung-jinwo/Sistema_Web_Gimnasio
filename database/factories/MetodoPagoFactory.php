<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MetodoPagoFactory extends Factory
{
    public function definition(): array
    {
        $nombre = $this->faker->randomElement(['Efectivo', 'Tarjeta', 'Yape', 'Plin', 'Transferencia']);

        return [
            'metod_nombre' => $nombre,
            'es_efectivo' => $nombre === 'Efectivo',
        ];
    }
}
