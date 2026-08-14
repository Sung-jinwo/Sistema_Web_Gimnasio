<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\MetodoPago;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VentaFactory extends Factory
{
    public function definition(): array
    {
        $tipos = ['producto', 'membresia', 'rapida'];
        $tipo = $this->faker->randomElement($tipos);

        return [
            'fkalum' => $tipo === 'rapida' ? null : Alumno::factory(),
            'fkusers' => User::factory(),
            'fksede' => Sede::factory(),
            'fkmetodo' => MetodoPago::factory(),
            'tipo_venta' => $tipo,
            'estado_venta' => $this->faker->randomElement(['completado', 'reservado', 'incompleto']),
            'estado_pago' => $this->faker->randomElement(['pagado', 'parcial', 'pendiente']),
            'venta_total' => $this->faker->randomFloat(2, 10, 1000),
            'venta_descuento' => $this->faker->randomFloat(2, 0, 50),
            'monto_pagado' => $this->faker->randomFloat(2, 0, 1000),
            'saldo' => $this->faker->randomFloat(2, 0, 500),
        ];
    }
}
