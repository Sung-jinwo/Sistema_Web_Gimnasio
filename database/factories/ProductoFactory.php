<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'prod_nombre' => $this->faker->word.' '.$this->faker->colorName,
            'prod_precio' => $this->faker->randomFloat(2, 10, 500),
            'prod_cantidad' => $this->faker->numberBetween(0, 100),
            'prod_stock_minimo' => 5,
            'fkcategoria' => Categoria::factory(),
            'fksede' => Sede::factory(),
            'fkusers' => User::factory(),
        ];
    }
}
