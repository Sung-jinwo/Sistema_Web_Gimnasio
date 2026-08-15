<?php

namespace Database\Factories;

use App\Models\Sede;
use App\Models\Sexo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlumnoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'alum_codigo' => 'ALU'.$this->faker->unique()->numberBetween(1000, 9999),
            'alum_nombre' => $this->faker->firstName,
            'alum_apellido' => $this->faker->lastName,
            'alum_numDoc' => $this->faker->unique()->numerify('########'),
            'alum_documento' => 'DNI',
            'fksexo' => fn () => Sexo::firstOrCreate(['sexo_nombre' => 'Masculino'])->id_sexo,
            'fecha_nac' => $this->faker->dateTimeBetween('-30 years', '-18 years')->format('Y-m-d'),
            'alum_telefo' => $this->faker->numerify('9########'),
            'alum_direccion' => $this->faker->address,
            'fksede' => Sede::factory(),
            'fkuser' => User::factory(),
            'alum_estado' => true,
        ];
    }
}
