<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\CategoriaGasto;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::table('sexo')->insert([
            ['id_sexo' => 1, 'sexo_nombre' => 'Masculino'],
            ['id_sexo' => 2, 'sexo_nombre' => 'Femenino'],
        ]);

        $sede = Sede::create([
            'sede_nombre' => 'Sede Principal',
            'sede_direccion' => 'Av. Principal 123',
            'sede_telefono' => '000000000',
            'sede_responsable' => 'Administrador',
            'sede_horario' => 'Lun-Sab 6:00-22:00',
        ]);

        $metodos = ['Efectivo', 'Tarjeta', 'Transferencia', 'Yape/Plin'];
        foreach ($metodos as $metodo) {
            MetodoPago::create(['metod_nombre' => $metodo]);
        }

        $categoriasProducto = ['Suplementos', 'Bebidas', 'Accesorios', 'Ropa', 'Equipamiento'];
        foreach ($categoriasProducto as $cat) {
            Categoria::create(['cat_nombre' => $cat]);
        }

        $categoriasGasto = ['Mantenimiento', 'Servicios', 'Suministros', 'Nomina', 'Otros'];
        foreach ($categoriasGasto as $cat) {
            CategoriaGasto::create(['cat_nombre' => $cat]);
        }

        $planes = [
            ['mem_nombre' => 'Diaria', 'mem_precio' => 10, 'mem_duracion' => 1, 'mem_categoria' => 'Regular', 'mem_tipo' => 'Diaria'],
            ['mem_nombre' => 'Semanal', 'mem_precio' => 50, 'mem_duracion' => 7, 'mem_categoria' => 'Regular', 'mem_tipo' => 'Semanal'],
            ['mem_nombre' => 'Mensual', 'mem_precio' => 150, 'mem_duracion' => 30, 'mem_categoria' => 'Regular', 'mem_tipo' => 'Mensual'],
            ['mem_nombre' => 'Trimestral', 'mem_precio' => 400, 'mem_duracion' => 90, 'mem_categoria' => 'Premium', 'mem_tipo' => 'Trimestral'],
            ['mem_nombre' => 'Semestral', 'mem_precio' => 750, 'mem_duracion' => 180, 'mem_categoria' => 'Premium', 'mem_tipo' => 'Semestral'],
            ['mem_nombre' => 'Anual', 'mem_precio' => 1400, 'mem_duracion' => 365, 'mem_categoria' => 'VIP', 'mem_tipo' => 'Anual'],
        ];
        foreach ($planes as $plan) {
            Membresia::create($plan);
        }

        User::create([
            'name' => 'Administrador',
            'email' => 'admin@gym.com',
            'password' => bcrypt('admin123'),
            'rol' => User::ROL_ADMIN,
            'fksede' => $sede->id_sede,
            'estado' => true,
        ]);

        User::create([
            'name' => 'Recepcionista',
            'email' => 'recepcion@gym.com',
            'password' => bcrypt('recepcion123'),
            'rol' => User::ROL_ASISTENCIA,
            'fksede' => $sede->id_sede,
            'estado' => true,
        ]);

        User::create([
            'name' => 'Asesor Ventas',
            'email' => 'ventas@gym.com',
            'password' => bcrypt('ventas123'),
            'rol' => User::ROL_VENTAS,
            'fksede' => $sede->id_sede,
            'estado' => true,
        ]);
    }
}
