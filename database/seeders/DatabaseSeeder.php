<?php

namespace Database\Seeders;

use App\Models\Alumno;
use App\Models\Caja;
use App\Models\Categoria;
use App\Models\CategoriaGasto;
use App\Models\Comision;
use App\Models\Cuota;
use App\Models\DetalleVenta;
use App\Models\Gasto;
use App\Models\Membresia;
use App\Models\MembresiaAlumno;
use App\Models\MetodoPago;
use App\Models\MovimientoCaja;
use App\Models\Padre;
use App\Models\Pago;
use App\Models\PagoDetalle;
use App\Models\Producto;
use App\Models\Sede;
use App\Models\User;
use App\Models\Venta;
use App\Models\Asistencia;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
        ]);

        DB::table('sexo')->insertOrIgnore([
            ['id_sexo' => 1, 'sexo_nombre' => 'Masculino'],
            ['id_sexo' => 2, 'sexo_nombre' => 'Femenino'],
        ]);

        $sede = Sede::firstOrCreate(
            ['sede_nombre' => 'Sede Principal'],
            [
                'sede_direccion' => 'Av. Principal 123',
                'sede_telefono' => '000000000',
                'sede_responsable' => 'Administrador',
                'sede_horario' => 'Lun-Sab 6:00-22:00',
            ]
        );

        $sede2 = Sede::firstOrCreate(
            ['sede_nombre' => 'Sede Norte'],
            [
                'sede_direccion' => 'Calle Los Olivos 456',
                'sede_telefono' => '111111111',
                'sede_responsable' => 'Carlos Mendoza',
                'sede_horario' => 'Lun-Dom 7:00-21:00',
            ]
        );

        $metodos = ['Efectivo', 'Tarjeta', 'Transferencia', 'Yape/Plin'];
        foreach ($metodos as $metodo) {
            MetodoPago::firstOrCreate(['metod_nombre' => $metodo]);
        }

        $categoriasProducto = ['Suplementos', 'Bebidas', 'Accesorios', 'Ropa', 'Equipamiento'];
        foreach ($categoriasProducto as $cat) {
            Categoria::firstOrCreate(['cat_nombre' => $cat]);
        }

        $categoriasGasto = ['Mantenimiento', 'Servicios', 'Suministros', 'Nomina', 'Otros'];
        foreach ($categoriasGasto as $cat) {
            CategoriaGasto::firstOrCreate(['cat_nombre' => $cat]);
        }

        $planes = [
            ['mem_nombre' => 'Diaria', 'mem_precio' => 10, 'mem_duracion' => 1, 'mem_categoria' => 'Regular', 'mem_tipo' => 'Diaria', 'comision' => 5, 'modalidad' => 'por_fechas'],
            ['mem_nombre' => 'Semanal', 'mem_precio' => 50, 'mem_duracion' => 7, 'mem_categoria' => 'Regular', 'mem_tipo' => 'Semanal', 'comision' => 8, 'modalidad' => 'por_fechas'],
            ['mem_nombre' => 'Mensual', 'mem_precio' => 150, 'mem_duracion' => 30, 'mem_categoria' => 'Regular', 'mem_tipo' => 'Mensual', 'comision' => 10, 'modalidad' => 'por_meses'],
            ['mem_nombre' => 'Trimestral', 'mem_precio' => 400, 'mem_duracion' => 90, 'mem_categoria' => 'Premium', 'mem_tipo' => 'Trimestral', 'comision' => 12, 'modalidad' => 'por_meses'],
            ['mem_nombre' => 'Semestral', 'mem_precio' => 750, 'mem_duracion' => 180, 'mem_categoria' => 'Premium', 'mem_tipo' => 'Semestral', 'comision' => 15, 'modalidad' => 'por_meses'],
            ['mem_nombre' => 'Anual', 'mem_precio' => 1400, 'mem_duracion' => 255, 'mem_categoria' => 'VIP', 'mem_tipo' => 'Anual', 'comision' => 20, 'modalidad' => 'por_meses'],
        ];
        foreach ($planes as $plan) {
            Membresia::firstOrCreate(['mem_nombre' => $plan['mem_nombre']], $plan);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@gym.com'],
            [
                'name' => 'Administrador',
                'password' => bcrypt('admin123'),
                'rol' => User::ROL_ADMIN,
                'fksede' => $sede->id_sede,
                'estado' => true,
            ]
        );

        $recepcion = User::firstOrCreate(
            ['email' => 'recepcion@gym.com'],
            [
                'name' => 'Recepcionista',
                'password' => bcrypt('recepcion123'),
                'rol' => User::ROL_ASISTENCIA,
                'fksede' => $sede->id_sede,
                'estado' => true,
            ]
        );

        $ventasUser = User::firstOrCreate(
            ['email' => 'ventas@gym.com'],
            [
                'name' => 'Asesor Ventas',
                'password' => bcrypt('ventas123'),
                'rol' => User::ROL_VENTAS,
                'fksede' => $sede->id_sede,
                'estado' => true,
            ]
        );

        $empleado = User::firstOrCreate(
            ['email' => 'empleado@gym.com'],
            [
                'name' => 'Empleado Local',
                'password' => bcrypt('empleado123'),
                'rol' => User::ROL_EMPLEADO,
                'fksede' => $sede2->id_sede,
                'estado' => true,
            ]
        );

        $alumnosData = [
            ['alum_codigo' => 'ALU001', 'alum_nombre' => 'Juan Carlos', 'alum_apellido' => 'García López', 'fksexo' => 1, 'fecha_nac' => '1995-03-15', 'fksede' => $sede->id_sede, 'alum_documento' => 'DNI', 'alum_numDoc' => '12345678', 'alum_telefo' => '987654321', 'alum_correro' => 'juan.garcia@email.com', 'alum_direccion' => 'Av. Los Pinos 123', 'fkuser' => $admin->id],
            ['alum_codigo' => 'ALU002', 'alum_nombre' => 'María Fernanda', 'alum_apellido' => 'Rodríguez Sánchez', 'fksexo' => 2, 'fecha_nac' => '1998-07-22', 'fksede' => $sede->id_sede, 'alum_documento' => 'DNI', 'alum_numDoc' => '87654321', 'alum_telefo' => '912345678', 'alum_correro' => 'maria.rodriguez@email.com', 'alum_direccion' => 'Calle Las Flores 456', 'fkuser' => $admin->id],
            ['alum_codigo' => 'ALU003', 'alum_nombre' => 'Carlos Alberto', 'alum_apellido' => 'Mendoza Ruiz', 'fksexo' => 1, 'fecha_nac' => '1990-11-08', 'fksede' => $sede->id_sede, 'alum_documento' => 'DNI', 'alum_numDoc' => '11223344', 'alum_telefo' => '945678912', 'alum_correro' => 'carlos.mendoza@email.com', 'alum_direccion' => 'Jr. La Paz 789', 'fkuser' => $admin->id],
            ['alum_codigo' => 'ALU004', 'alum_nombre' => 'Ana Lucía', 'alum_apellido' => 'Torres Vargas', 'fksexo' => 2, 'fecha_nac' => '2005-05-30', 'fksede' => $sede->id_sede, 'alum_documento' => 'DNI', 'alum_numDoc' => '55667788', 'alum_telefo' => '956789123', 'alum_correro' => 'ana.torres@email.com', 'alum_direccion' => 'Av. El Sol 321', 'fkuser' => $admin->id],
            ['alum_codigo' => 'ALU005', 'alum_nombre' => 'Roberto José', 'alum_apellido' => 'Herrera Castillo', 'fksexo' => 1, 'fecha_nac' => '1988-01-12', 'fksede' => $sede2->id_sede, 'alum_documento' => 'DNI', 'alum_numDoc' => '99887766', 'alum_telefo' => '967891234', 'alum_correro' => 'roberto.herrera@email.com', 'alum_direccion' => 'Calle Los Robles 654', 'fkuser' => $empleado->id],
            ['alum_codigo' => 'ALU006', 'alum_nombre' => 'Sofía Isabel', 'alum_apellido' => 'Ramírez Delgado', 'fksexo' => 2, 'fecha_nac' => '1993-09-18', 'fksede' => $sede->id_sede, 'alum_documento' => 'DNI', 'alum_numDoc' => '44332211', 'alum_telefo' => '978912345', 'alum_correro' => 'sofia.ramirez@email.com', 'alum_direccion' => 'Av. La Marina 987', 'fkuser' => $admin->id],
            ['alum_codigo' => 'ALU007', 'alum_nombre' => 'Diego Alejandro', 'alum_apellido' => 'Vargas Morales', 'fksexo' => 1, 'fecha_nac' => '2000-12-05', 'fksede' => $sede->id_sede, 'alum_documento' => 'DNI', 'alum_numDoc' => '66778899', 'alum_telefo' => '989123456', 'alum_correro' => 'diego.vargas@email.com', 'alum_direccion' => 'Jr. Arequipa 147', 'fkuser' => $admin->id],
            ['alum_codigo' => 'ALU008', 'alum_nombre' => 'Valentina', 'alum_apellido' => 'Castro Jiménez', 'fksexo' => 2, 'fecha_nac' => '2007-04-25', 'fksede' => $sede2->id_sede, 'alum_documento' => 'DNI', 'alum_numDoc' => '33445566', 'alum_telefo' => '991234567', 'alum_correro' => 'valentina.castro@email.com', 'alum_direccion' => 'Calle San Martín 258', 'fkuser' => $empleado->id],
            ['alum_codigo' => 'ALU009', 'alum_nombre' => 'Luis Miguel', 'alum_apellido' => 'Paredes Silva', 'fksexo' => 1, 'fecha_nac' => '1992-06-14', 'fksede' => $sede->id_sede, 'alum_documento' => 'DNI', 'alum_numDoc' => '77889900', 'alum_telefo' => '912345670', 'alum_correro' => 'luis.paredes@email.com', 'alum_direccion' => 'Av. Brasil 369', 'fkuser' => $admin->id],
            ['alum_codigo' => 'ALU010', 'alum_nombre' => 'Camila Andrea', 'alum_apellido' => 'Flores Benítez', 'fksexo' => 2, 'fecha_nac' => '1997-02-28', 'fksede' => $sede->id_sede, 'alum_documento' => 'DNI', 'alum_numDoc' => '22334455', 'alum_telefo' => '923456789', 'alum_correro' => 'camila.flores@email.com', 'alum_direccion' => 'Jr. Ucayali 741', 'fkuser' => $admin->id],
            ['alum_codigo' => 'ALU011', 'alum_nombre' => 'Fernando', 'alum_apellido' => 'Ríos Acosta', 'fksexo' => 1, 'fecha_nac' => '1985-08-20', 'fksede' => $sede->id_sede, 'alum_documento' => 'DNI', 'alum_numDoc' => '10203040', 'alum_telefo' => '934567890', 'alum_correro' => 'fernando.rios@email.com', 'alum_direccion' => 'Av. España 852', 'alum_condi' => 'Hipertensión controlada', 'fkuser' => $admin->id],
            ['alum_codigo' => 'ALU012', 'alum_nombre' => 'Gabriela', 'alum_apellido' => 'Chávez Núñez', 'fksexo' => 2, 'fecha_nac' => '1999-10-10', 'fksede' => $sede2->id_sede, 'alum_documento' => 'DNI', 'alum_numDoc' => '50607080', 'alum_telefo' => '945678901', 'alum_correro' => 'gabriela.chavez@email.com', 'alum_direccion' => 'Calle Lima 963', 'fkuser' => $empleado->id],
        ];

        $alumnos = [];
        foreach ($alumnosData as $data) {
            $alumnos[] = Alumno::firstOrCreate(['alum_codigo' => $data['alum_codigo']], $data);
        }

        $padresData = [
            ['padre_nombre' => 'María', 'padre_apellido' => 'Torres Vargas', 'padre_telefono' => '999888777', 'padre_parentesco' => 'Madre', 'fkalumno' => $alumnos[3]->id_alumno],
            ['padre_nombre' => 'Roberto', 'padre_apellido' => 'Castro', 'padre_telefono' => '988776655', 'padre_parentesco' => 'Padre', 'fkalumno' => $alumnos[7]->id_alumno],
        ];
        foreach ($padresData as $data) {
            Padre::firstOrCreate(['fkalumno' => $data['fkalumno'], 'padre_nombre' => $data['padre_nombre']], $data);
        }

        $productosData = [
            ['prod_codigo' => 'SUP001', 'prod_nombre' => 'Whey Protein Gold Standard', 'prod_precio' => 180, 'prod_marca' => 'Optimum Nutrition', 'prod_cantidad' => 25, 'prod_stock_minimo' => 5, 'fkcategoria' => 1, 'fkusers' => $admin->id, 'fksede' => $sede->id_sede],
            ['prod_codigo' => 'SUP002', 'prod_nombre' => 'Creatina Monohidrato', 'prod_precio' => 85, 'prod_marca' => 'MuscleTech', 'prod_cantidad' => 40, 'prod_stock_minimo' => 10, 'fkcategoria' => 1, 'fkusers' => $admin->id, 'fksede' => $sede->id_sede],
            ['prod_codigo' => 'SUP003', 'prod_nombre' => 'BCAA 2:1:1', 'prod_precio' => 120, 'prod_marca' => 'Dymatize', 'prod_cantidad' => 15, 'prod_stock_minimo' => 5, 'fkcategoria' => 1, 'fkusers' => $admin->id, 'fksede' => $sede->id_sede],
            ['prod_codigo' => 'BEB001', 'prod_nombre' => 'Agua Mineral 500ml', 'prod_precio' => 2.5, 'prod_marca' => 'San Luis', 'prod_cantidad' => 100, 'prod_stock_minimo' => 20, 'fkcategoria' => 2, 'fkusers' => $admin->id, 'fksede' => $sede->id_sede],
            ['prod_codigo' => 'BEB002', 'prod_nombre' => 'Gatorade 1L', 'prod_precio' => 8, 'prod_marca' => 'Gatorade', 'prod_cantidad' => 50, 'prod_stock_minimo' => 15, 'fkcategoria' => 2, 'fkusers' => $admin->id, 'fksede' => $sede->id_sede],
            ['prod_codigo' => 'BEB003', 'prod_nombre' => 'Red Bull 250ml', 'prod_precio' => 10, 'prod_marca' => 'Red Bull', 'prod_cantidad' => 30, 'prod_stock_minimo' => 10, 'fkcategoria' => 2, 'fkusers' => $admin->id, 'fksede' => $sede->id_sede],
            ['prod_codigo' => 'ACC001', 'prod_nombre' => 'Guantes de Entrenamiento', 'prod_precio' => 45, 'prod_marca' => 'Everlast', 'prod_cantidad' => 20, 'prod_stock_minimo' => 5, 'fkcategoria' => 3, 'fkusers' => $admin->id, 'fksede' => $sede->id_sede],
            ['prod_codigo' => 'ACC002', 'prod_nombre' => 'Cuerda de Saltar', 'prod_precio' => 25, 'prod_marca' => 'Nike', 'prod_cantidad' => 35, 'prod_stock_minimo' => 10, 'fkcategoria' => 3, 'fkusers' => $admin->id, 'fksede' => $sede->id_sede],
            ['prod_codigo' => 'ACC003', 'prod_nombre' => 'Shaker 750ml', 'prod_precio' => 15, 'prod_marca' => 'BlenderBottle', 'prod_cantidad' => 60, 'prod_stock_minimo' => 15, 'fkcategoria' => 3, 'fkusers' => $admin->id, 'fksede' => $sede->id_sede],
            ['prod_codigo' => 'ROP001', 'prod_nombre' => 'Polo Dry-Fit', 'prod_precio' => 55, 'prod_marca' => 'Adidas', 'prod_cantidad' => 18, 'prod_stock_minimo' => 5, 'fkcategoria' => 4, 'fkusers' => $admin->id, 'fksede' => $sede->id_sede],
            ['prod_codigo' => 'ROP002', 'prod_nombre' => 'Short Deportivo', 'prod_precio' => 40, 'prod_marca' => 'Nike', 'prod_cantidad' => 22, 'prod_stock_minimo' => 5, 'fkcategoria' => 4, 'fkusers' => $admin->id, 'fksede' => $sede->id_sede],
            ['prod_codigo' => 'EQU001', 'prod_nombre' => 'Mancuerna 5kg', 'prod_precio' => 90, 'prod_marca' => 'Ruff', 'prod_cantidad' => 12, 'prod_stock_minimo' => 4, 'fkcategoria' => 5, 'fkusers' => $admin->id, 'fksede' => $sede->id_sede],
            ['prod_codigo' => 'EQU002', 'prod_nombre' => 'Banda Elástica', 'prod_precio' => 30, 'prod_marca' => 'TheraBand', 'prod_cantidad' => 40, 'prod_stock_minimo' => 10, 'fkcategoria' => 5, 'fkusers' => $admin->id, 'fksede' => $sede->id_sede],
            ['prod_codigo' => 'SUP004', 'prod_nombre' => 'Pre-Entreno C4', 'prod_precio' => 95, 'prod_marca' => 'Cellucor', 'prod_cantidad' => 0, 'prod_stock_minimo' => 5, 'fkcategoria' => 1, 'fkusers' => $empleado->id, 'fksede' => $sede2->id_sede],
        ];

        $productos = [];
        foreach ($productosData as $data) {
            $productos[] = Producto::firstOrCreate(['prod_codigo' => $data['prod_codigo']], $data);
        }

        $caja = Caja::firstOrCreate(
            ['fksede' => $sede->id_sede, 'fkuser' => $admin->id, 'estado' => 'abierta'],
            [
                'fecha_apertura' => now()->startOfDay(),
                'monto_inicial' => 200,
                'observacion' => 'Caja del día',
            ]
        );

        $hoy = now();
        $pagosData = [
            [
                'fkalum' => $alumnos[0]->id_alumno,
                'fkuser' => $admin->id,
                'fksede' => $sede->id_sede,
                'fkmetodo' => 1,
                'fkmem' => 3,
                'tipo_membresia' => 'principal',
                'pag_inicio' => $hoy->copy()->startOfMonth()->format('Y-m-d'),
                'pag_fin' => $hoy->copy()->endOfMonth()->format('Y-m-d'),
                'estado_pago' => 'completo',
                'pag_monto' => 150,
                'pag_descuento' => 0,
                'total' => 150,
                'monto_pagado' => 150,
                'saldo' => 0,
                'num_comprobante' => 'B001-0001',
            ],
            [
                'fkalum' => $alumnos[1]->id_alumno,
                'fkuser' => $admin->id,
                'fksede' => $sede->id_sede,
                'fkmetodo' => 4,
                'fkmem' => 4,
                'tipo_membresia' => 'principal',
                'pag_inicio' => $hoy->copy()->subDays(10)->format('Y-m-d'),
                'pag_fin' => $hoy->copy()->addDays(80)->format('Y-m-d'),
                'estado_pago' => 'completo',
                'pag_monto' => 400,
                'pag_descuento' => 20,
                'total' => 380,
                'monto_pagado' => 380,
                'saldo' => 0,
                'num_comprobante' => 'B001-0002',
            ],
            [
                'fkalum' => $alumnos[2]->id_alumno,
                'fkuser' => $admin->id,
                'fksede' => $sede->id_sede,
                'fkmetodo' => 1,
                'fkmem' => 3,
                'tipo_membresia' => 'principal',
                'pag_inicio' => $hoy->copy()->subDays(25)->format('Y-m-d'),
                'pag_fin' => $hoy->copy()->addDays(5)->format('Y-m-d'),
                'estado_pago' => 'incompleto',
                'pag_monto' => 150,
                'pag_descuento' => 0,
                'total' => 150,
                'monto_pagado' => 80,
                'saldo' => 70,
                'fecha_limite_pago' => $hoy->copy()->addDays(3)->format('Y-m-d'),
                'num_comprobante' => 'B001-0003',
            ],
            [
                'fkalum' => $alumnos[3]->id_alumno,
                'fkuser' => $admin->id,
                'fksede' => $sede->id_sede,
                'fkmetodo' => 3,
                'fkmem' => 2,
                'tipo_membresia' => 'principal',
                'pag_inicio' => $hoy->copy()->subDays(30)->format('Y-m-d'),
                'pag_fin' => $hoy->copy()->subDays(23)->format('Y-m-d'),
                'estado_pago' => 'incompleto',
                'pag_monto' => 50,
                'pag_descuento' => 0,
                'total' => 50,
                'monto_pagado' => 20,
                'saldo' => 30,
                'fecha_limite_pago' => $hoy->copy()->subDays(5)->format('Y-m-d'),
                'num_comprobante' => null,
                'observacion' => 'Alumna no completó el pago',
            ],
            [
                'fkalum' => $alumnos[4]->id_alumno,
                'fkuser' => $empleado->id,
                'fksede' => $sede2->id_sede,
                'fkmetodo' => 1,
                'fkmem' => 5,
                'tipo_membresia' => 'principal',
                'pag_inicio' => $hoy->copy()->startOfMonth()->format('Y-m-d'),
                'pag_fin' => $hoy->copy()->addMonths(6)->format('Y-m-d'),
                'estado_pago' => 'completo',
                'pag_monto' => 750,
                'pag_descuento' => 50,
                'total' => 700,
                'monto_pagado' => 700,
                'saldo' => 0,
                'num_comprobante' => 'B002-0001',
            ],
            [
                'fkalum' => $alumnos[5]->id_alumno,
                'fkuser' => $admin->id,
                'fksede' => $sede->id_sede,
                'fkmetodo' => 2,
                'fkmem' => 3,
                'tipo_membresia' => 'principal',
                'pag_inicio' => $hoy->copy()->subDays(45)->format('Y-m-d'),
                'pag_fin' => $hoy->copy()->subDays(15)->format('Y-m-d'),
                'estado_pago' => 'completo',
                'pag_monto' => 150,
                'pag_descuento' => 0,
                'total' => 150,
                'monto_pagado' => 150,
                'saldo' => 0,
                'num_comprobante' => 'B001-0004',
            ],
            [
                'fkalum' => $alumnos[6]->id_alumno,
                'fkuser' => $admin->id,
                'fksede' => $sede->id_sede,
                'fkmetodo' => 1,
                'fkmem' => 1,
                'tipo_membresia' => 'principal',
                'pag_inicio' => $hoy->format('Y-m-d'),
                'pag_fin' => $hoy->format('Y-m-d'),
                'estado_pago' => 'completo',
                'pag_monto' => 10,
                'pag_descuento' => 0,
                'total' => 10,
                'monto_pagado' => 10,
                'saldo' => 0,
                'num_comprobante' => 'B001-0005',
            ],
            [
                'fkalum' => $alumnos[8]->id_alumno,
                'fkuser' => $admin->id,
                'fksede' => $sede->id_sede,
                'fkmetodo' => 4,
                'fkmem' => 6,
                'tipo_membresia' => 'principal',
                'pag_inicio' => $hoy->copy()->startOfMonth()->format('Y-m-d'),
                'pag_fin' => $hoy->copy()->addYear()->format('Y-m-d'),
                'estado_pago' => 'incompleto',
                'pag_monto' => 1400,
                'pag_descuento' => 100,
                'total' => 1300,
                'monto_pagado' => 600,
                'saldo' => 700,
                'fecha_limite_pago' => $hoy->copy()->addDays(15)->format('Y-m-d'),
                'num_comprobante' => 'B001-0006',
            ],
            [
                'fkalum' => $alumnos[9]->id_alumno,
                'fkuser' => $admin->id,
                'fksede' => $sede->id_sede,
                'fkmetodo' => 1,
                'fkmem' => 3,
                'tipo_membresia' => 'principal',
                'pag_inicio' => $hoy->copy()->subDays(60)->format('Y-m-d'),
                'pag_fin' => $hoy->copy()->subDays(30)->format('Y-m-d'),
                'estado_pago' => 'completo',
                'pag_monto' => 150,
                'pag_descuento' => 0,
                'total' => 150,
                'monto_pagado' => 150,
                'saldo' => 0,
                'num_comprobante' => 'B001-0007',
            ],
        ];

        $pagos = [];
        foreach ($pagosData as $i => $data) {
            $key = 'SEED-PAGO-' . ($i + 1);
            $pago = Pago::where('observacion', $key)->first();
            if (!$pago) {
                $data['observacion'] = $key;
                $pago = Pago::create($data);
            }
            $pagos[] = $pago;
        }

        $pagoDetallesData = [
            ['fkpago' => $pagos[0]->id_pag, 'concepto' => 'Membresía Mensual', 'monto' => 150],
            ['fkpago' => $pagos[1]->id_pag, 'concepto' => 'Membresía Trimestral', 'monto' => 380],
            ['fkpago' => $pagos[2]->id_pag, 'concepto' => 'Membresía Mensual - Pago parcial', 'monto' => 80],
            ['fkpago' => $pagos[3]->id_pag, 'concepto' => 'Membresía Semanal - Pago parcial', 'monto' => 20],
            ['fkpago' => $pagos[4]->id_pag, 'concepto' => 'Membresía Semestral', 'monto' => 700],
            ['fkpago' => $pagos[5]->id_pag, 'concepto' => 'Membresía Mensual', 'monto' => 150],
            ['fkpago' => $pagos[6]->id_pag, 'concepto' => 'Membresía Diaria', 'monto' => 10],
            ['fkpago' => $pagos[7]->id_pag, 'concepto' => 'Membresía Anual - Pago inicial', 'monto' => 600],
            ['fkpago' => $pagos[8]->id_pag, 'concepto' => 'Membresía Mensual', 'monto' => 150],
        ];
        foreach ($pagoDetallesData as $data) {
            PagoDetalle::firstOrCreate(['fkpago' => $data['fkpago'], 'concepto' => $data['concepto']], $data);
        }

        $membresiasAlumnoData = [
            ['fkalumno' => $alumnos[0]->id_alumno, 'fkmem' => 3, 'fecha_inicio' => $hoy->copy()->startOfMonth()->format('Y-m-d'), 'fecha_fin' => $hoy->copy()->endOfMonth()->format('Y-m-d'), 'precio_vendido' => 150, 'comision_aplicada' => 15, 'modalidad' => 'por_meses', 'estado' => 'activa'],
            ['fkalumno' => $alumnos[1]->id_alumno, 'fkmem' => 4, 'fecha_inicio' => $hoy->copy()->subDays(10)->format('Y-m-d'), 'fecha_fin' => $hoy->copy()->addDays(80)->format('Y-m-d'), 'precio_vendido' => 380, 'comision_aplicada' => 45.6, 'modalidad' => 'por_meses', 'estado' => 'activa'],
            ['fkalumno' => $alumnos[2]->id_alumno, 'fkmem' => 3, 'fecha_inicio' => $hoy->copy()->subDays(25)->format('Y-m-d'), 'fecha_fin' => $hoy->copy()->addDays(5)->format('Y-m-d'), 'precio_vendido' => 150, 'comision_aplicada' => 15, 'modalidad' => 'por_meses', 'estado' => 'activa'],
            ['fkalumno' => $alumnos[4]->id_alumno, 'fkmem' => 5, 'fecha_inicio' => $hoy->copy()->startOfMonth()->format('Y-m-d'), 'fecha_fin' => $hoy->copy()->addMonths(6)->format('Y-m-d'), 'precio_vendido' => 700, 'comision_aplicada' => 105, 'modalidad' => 'por_meses', 'estado' => 'activa'],
            ['fkalumno' => $alumnos[6]->id_alumno, 'fkmem' => 1, 'fecha_inicio' => $hoy->format('Y-m-d'), 'fecha_fin' => $hoy->format('Y-m-d'), 'precio_vendido' => 10, 'comision_aplicada' => 0.5, 'modalidad' => 'por_fechas', 'estado' => 'activa'],
            ['fkalumno' => $alumnos[8]->id_alumno, 'fkmem' => 6, 'fecha_inicio' => $hoy->copy()->startOfMonth()->format('Y-m-d'), 'fecha_fin' => $hoy->copy()->addYear()->format('Y-m-d'), 'precio_vendido' => 1300, 'comision_aplicada' => 260, 'modalidad' => 'por_meses', 'estado' => 'activa'],
        ];
        foreach ($membresiasAlumnoData as $data) {
            MembresiaAlumno::firstOrCreate(['fkalumno' => $data['fkalumno'], 'fkmem' => $data['fkmem'], 'fecha_inicio' => $data['fecha_inicio']], $data);
        }

        $ventasData = [
            [
                'fkalum' => $alumnos[0]->id_alumno,
                'fkusers' => $admin->id,
                'fksede' => $sede->id_sede,
                'fkmetodo' => 1,
                'fkproducto' => $productos[0]->id_productos,
                'tipo_venta' => 'producto',
                'estado_venta' => 'completado',
                'estado_pago' => 'pagado',
                'venta_total' => 180,
                'monto_pagado' => 180,
                'saldo' => 0,
                'venta_fecha' => $hoy->format('Y-m-d'),
                'observacion' => 'SEED-VENTA-1',
            ],
            [
                'fkalum' => $alumnos[1]->id_alumno,
                'fkusers' => $ventasUser->id,
                'fksede' => $sede->id_sede,
                'fkmetodo' => 4,
                'fkproducto' => $productos[4]->id_productos,
                'tipo_venta' => 'producto',
                'estado_venta' => 'completado',
                'estado_pago' => 'pagado',
                'venta_total' => 24,
                'monto_pagado' => 24,
                'saldo' => 0,
                'venta_fecha' => $hoy->format('Y-m-d'),
                'observacion' => 'SEED-VENTA-2',
            ],
            [
                'fkalum' => $alumnos[2]->id_alumno,
                'fkusers' => $admin->id,
                'fksede' => $sede->id_sede,
                'fkmetodo' => 1,
                'fkproducto' => $productos[6]->id_productos,
                'tipo_venta' => 'producto',
                'estado_venta' => 'reservado',
                'estado_pago' => 'parcial',
                'venta_total' => 45,
                'monto_pagado' => 20,
                'saldo' => 25,
                'venta_fecha' => $hoy->copy()->addDays(3)->format('Y-m-d'),
                'fecha_acordada' => $hoy->copy()->addDays(3)->format('Y-m-d'),
                'observacion' => 'SEED-VENTA-3',
            ],
            [
                'fkalum' => $alumnos[5]->id_alumno,
                'fkusers' => $ventasUser->id,
                'fksede' => $sede->id_sede,
                'fkmetodo' => 2,
                'fkproducto' => null,
                'tipo_venta' => 'rapida',
                'estado_venta' => 'completado',
                'estado_pago' => 'pagado',
                'venta_total' => 35,
                'monto_pagado' => 35,
                'saldo' => 0,
                'venta_fecha' => $hoy->format('Y-m-d'),
                'observacion' => 'SEED-VENTA-4 - Venta rápida - Toalla y agua',
            ],
            [
                'fkalum' => $alumnos[7]->id_alumno,
                'fkusers' => $empleado->id,
                'fksede' => $sede2->id_sede,
                'fkmetodo' => 1,
                'fkproducto' => $productos[8]->id_productos,
                'tipo_venta' => 'producto',
                'estado_venta' => 'completado',
                'estado_pago' => 'pagado',
                'venta_total' => 30,
                'monto_pagado' => 30,
                'saldo' => 0,
                'venta_fecha' => $hoy->format('Y-m-d'),
                'observacion' => 'SEED-VENTA-5',
            ],
            [
                'fkalum' => $alumnos[9]->id_alumno,
                'fkusers' => $admin->id,
                'fksede' => $sede->id_sede,
                'fkmetodo' => 1,
                'fkproducto' => $productos[11]->id_productos,
                'tipo_venta' => 'producto',
                'estado_venta' => 'completado',
                'estado_pago' => 'pagado',
                'venta_total' => 180,
                'monto_pagado' => 180,
                'saldo' => 0,
                'venta_fecha' => $hoy->format('Y-m-d'),
                'observacion' => 'SEED-VENTA-6',
            ],
        ];

        $ventas = [];
        foreach ($ventasData as $data) {
            $key = $data['observacion'];
            $venta = Venta::where('observacion', 'like', 'SEED-VENTA-%')->where('observacion', $key)->first();
            if (!$venta) {
                $venta = Venta::create($data);
            }
            $ventas[] = $venta;
        }

        $detalleVentasData = [
            ['fkventa' => $ventas[0]->id_venta, 'fkproducto' => $productos[0]->id_productos, 'cantidad' => 1, 'precio_unitario' => 180, 'subtotal' => 180],
            ['fkventa' => $ventas[1]->id_venta, 'fkproducto' => $productos[4]->id_productos, 'cantidad' => 3, 'precio_unitario' => 8, 'subtotal' => 24],
            ['fkventa' => $ventas[2]->id_venta, 'fkproducto' => $productos[6]->id_productos, 'cantidad' => 1, 'precio_unitario' => 45, 'subtotal' => 45],
            ['fkventa' => $ventas[4]->id_venta, 'fkproducto' => $productos[8]->id_productos, 'cantidad' => 2, 'precio_unitario' => 15, 'subtotal' => 30],
            ['fkventa' => $ventas[5]->id_venta, 'fkproducto' => $productos[11]->id_productos, 'cantidad' => 2, 'precio_unitario' => 90, 'subtotal' => 180],
        ];
        foreach ($detalleVentasData as $data) {
            DetalleVenta::firstOrCreate(['fkventa' => $data['fkventa'], 'fkproducto' => $data['fkproducto']], $data);
        }

        $cuotasData = [
            ['fkpago' => $pagos[2]->id_pag, 'numero_cuota' => 1, 'monto' => 75, 'monto_pagado' => 75, 'saldo' => 0, 'fecha_acordada' => $hoy->copy()->subDays(20)->format('Y-m-d'), 'fecha_pago_real' => $hoy->copy()->subDays(20)->format('Y-m-d'), 'estado' => 'pagada'],
            ['fkpago' => $pagos[2]->id_pag, 'numero_cuota' => 2, 'monto' => 75, 'monto_pagado' => 5, 'saldo' => 70, 'fecha_acordada' => $hoy->copy()->addDays(3)->format('Y-m-d'), 'estado' => 'pendiente'],
            ['fkpago' => $pagos[7]->id_pag, 'numero_cuota' => 1, 'monto' => 650, 'monto_pagado' => 600, 'saldo' => 50, 'fecha_acordada' => $hoy->copy()->startOfMonth()->format('Y-m-d'), 'fecha_pago_real' => $hoy->copy()->startOfMonth()->format('Y-m-d'), 'estado' => 'parcial'],
            ['fkpago' => $pagos[7]->id_pag, 'numero_cuota' => 2, 'monto' => 650, 'monto_pagado' => 0, 'saldo' => 650, 'fecha_acordada' => $hoy->copy()->addDays(15)->format('Y-m-d'), 'estado' => 'pendiente'],
            ['fkventa' => $ventas[2]->id_venta, 'numero_cuota' => 1, 'monto' => 20, 'monto_pagado' => 20, 'saldo' => 0, 'fecha_acordada' => $hoy->format('Y-m-d'), 'fecha_pago_real' => $hoy->format('Y-m-d'), 'estado' => 'pagada'],
            ['fkventa' => $ventas[2]->id_venta, 'numero_cuota' => 2, 'monto' => 25, 'monto_pagado' => 0, 'saldo' => 25, 'fecha_acordada' => $hoy->copy()->addDays(3)->format('Y-m-d'), 'estado' => 'pendiente'],
        ];
        foreach ($cuotasData as $data) {
            $key = ['fkpago' => $data['fkpago'] ?? null, 'fkventa' => $data['fkventa'] ?? null, 'numero_cuota' => $data['numero_cuota']];
            Cuota::firstOrCreate($key, $data);
        }

        if (Asistencia::count() === 0) {
            $asistenciasData = [];
            foreach ($alumnos as $alumno) {
                $diasAleatorios = rand(3, 15);
                for ($d = 0; $d < $diasAleatorios; $d++) {
                    $asistenciasData[] = [
                        'fkalum' => $alumno->id_alumno,
                        'fkuser' => $recepcion->id,
                        'fksede' => $alumno->fksede,
                        'visi_fecha' => $hoy->copy()->subDays(rand(0, 30))->setTime(rand(6, 21), rand(0, 59)),
                        'tipo_ingreso' => ['codigo', 'dni', 'qr'][rand(0, 2)],
                    ];
                }
            }
            foreach ($asistenciasData as $data) {
                Asistencia::create($data);
            }
        }

        $gastosData = [
            [
                'fksede' => $sede->id_sede,
                'fkuser' => $admin->id,
                'fkcategoria' => 1,
                'gas_fecha' => $hoy->copy()->subDays(5)->format('Y-m-d'),
                'gas_concepto' => 'Reparación de equipos de cardio',
                'gas_monto' => 350,
                'gas_observacion' => 'Cambio de correa en 2 cintas',
                'estado' => 'aprobado',
                'aprobado_por' => $admin->id,
                'fecha_aprobacion' => $hoy->copy()->subDays(4)->format('Y-m-d H:i:s'),
            ],
            [
                'fksede' => $sede->id_sede,
                'fkuser' => $admin->id,
                'fkcategoria' => 2,
                'gas_fecha' => $hoy->copy()->subDays(10)->format('Y-m-d'),
                'gas_concepto' => 'Recibo de luz',
                'gas_monto' => 800,
                'gas_observacion' => 'Mes anterior',
                'estado' => 'aprobado',
                'aprobado_por' => $admin->id,
                'fecha_aprobacion' => $hoy->copy()->subDays(9)->format('Y-m-d H:i:s'),
            ],
            [
                'fksede' => $sede->id_sede,
                'fkuser' => $empleado->id,
                'fkcategoria' => 4,
                'gas_fecha' => $hoy->copy()->subDays(3)->format('Y-m-d'),
                'gas_concepto' => 'Pago instructor de yoga',
                'gas_monto' => 500,
                'gas_observacion' => 'Clases del mes',
                'estado' => 'pendiente',
            ],
            [
                'fksede' => $sede->id_sede,
                'fkuser' => $admin->id,
                'fkcategoria' => 3,
                'gas_fecha' => $hoy->copy()->subDays(2)->format('Y-m-d'),
                'gas_concepto' => 'Compra de toallas y productos de limpieza',
                'gas_monto' => 120,
                'gas_observacion' => 'Reposición mensual',
                'estado' => 'pendiente',
            ],
            [
                'fksede' => $sede2->id_sede,
                'fkuser' => $empleado->id,
                'fkcategoria' => 5,
                'gas_fecha' => $hoy->copy()->subDays(7)->format('Y-m-d'),
                'gas_concepto' => 'Publicidad en redes sociales',
                'gas_monto' => 200,
                'gas_observacion' => 'Campaña Facebook e Instagram',
                'estado' => 'rechazado',
                'aprobado_por' => $admin->id,
                'fecha_aprobacion' => $hoy->copy()->subDays(6)->format('Y-m-d H:i:s'),
                'motivo_rechazo' => 'No se presentó la cotización previa',
            ],
        ];
        foreach ($gastosData as $data) {
            Gasto::firstOrCreate(['gas_concepto' => $data['gas_concepto'], 'fksede' => $data['fksede']], $data);
        }

        if (MovimientoCaja::count() === 0) {
            $movimientosData = [
                ['fkcaja' => $caja->id_caja, 'fkuser' => $admin->id, 'tipo' => 'ingreso', 'monto' => 150, 'concepto' => 'Pago membresía - Juan García'],
                ['fkcaja' => $caja->id_caja, 'fkuser' => $admin->id, 'tipo' => 'ingreso', 'monto' => 180, 'concepto' => 'Venta producto - Whey Protein'],
                ['fkcaja' => $caja->id_caja, 'fkuser' => $admin->id, 'tipo' => 'ingreso', 'monto' => 24, 'concepto' => 'Venta producto - Gatorade x3'],
                ['fkcaja' => $caja->id_caja, 'fkuser' => $admin->id, 'tipo' => 'ingreso', 'monto' => 10, 'concepto' => 'Pago membresía diaria - Diego Vargas'],
                ['fkcaja' => $caja->id_caja, 'fkuser' => $admin->id, 'tipo' => 'egreso', 'monto' => 50, 'concepto' => 'Compra de agua embotellada'],
            ];
            foreach ($movimientosData as $data) {
                MovimientoCaja::create($data);
            }
        }

        if (Comision::count() === 0) {
            $comisionesData = [
                [
                    'fkuser' => $ventasUser->id,
                    'fkcaja' => $caja->id_caja,
                    'fkventa' => $ventas[1]->id_venta,
                    'porcentaje' => 8,
                    'monto' => 24,
                    'tipo' => 'venta',
                    'comision_base' => 1.92,
                    'penalizacion' => 0,
                    'comision_final' => 1.92,
                    'estado' => 'pendiente',
                    'fecha_acordada_pago' => $hoy->copy()->addDays(15)->format('Y-m-d'),
                ],
                [
                    'fkuser' => $ventasUser->id,
                    'fkcaja' => $caja->id_caja,
                    'fkventa' => $ventas[3]->id_venta,
                    'porcentaje' => 5,
                    'monto' => 35,
                    'tipo' => 'venta',
                    'comision_base' => 1.75,
                    'penalizacion' => 0,
                    'comision_final' => 1.75,
                    'estado' => 'pendiente',
                    'fecha_acordada_pago' => $hoy->copy()->addDays(15)->format('Y-m-d'),
                ],
                [
                    'fkuser' => $admin->id,
                    'fkcaja' => $caja->id_caja,
                    'fkventa' => null,
                    'porcentaje' => 10,
                    'monto' => 150,
                    'tipo' => 'membresia',
                    'comision_base' => 15,
                    'penalizacion' => 0,
                    'comision_final' => 15,
                    'estado' => 'liquidada',
                    'fecha_acordada_pago' => $hoy->copy()->subDays(10)->format('Y-m-d'),
                    'fecha_pago_real' => $hoy->copy()->subDays(9)->format('Y-m-d'),
                ],
            ];
            foreach ($comisionesData as $data) {
                Comision::create($data);
            }
        }
    }
}
