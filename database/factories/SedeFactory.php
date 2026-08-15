<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SedeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sede_nombre' => $this->faker->company.' - Sede',
            'sede_direccion' => $this->faker->address,
            'sede_telefono' => $this->faker->numerify('01-#######'),
            'sede_estado' => true,
        ];
    }
}
