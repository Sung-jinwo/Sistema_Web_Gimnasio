<?php

namespace Tests\Feature\Ventas;

use App\Models\Alumno;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Sede;
use App\Models\User;
use App\Services\MembresiaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AbreCaja;
use Tests\TestCase;

class BusquedaYVigenciaTest extends TestCase
{
    use AbreCaja;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_alumnos_se_buscan_solo_por_dni_y_respetan_la_sede(): void
    {
        $sede = Sede::factory()->create();
        $otraSede = Sede::factory()->create();
        $local = User::factory()->create(['fksede' => $sede->id_sede]);
        $local->assignRole('Local');

        $propio = Alumno::factory()->create([
            'fksede' => $sede->id_sede,
            'alum_nombre' => 'Carlos',
            'alum_codigo' => 'ALU987654',
            'alum_numDoc' => '74125896',
            'alum_estado' => true,
        ]);
        Alumno::factory()->create([
            'fksede' => $otraSede->id_sede,
            'alum_numDoc' => '74120000',
            'alum_estado' => true,
        ]);

        $this->actingAs($local)->getJson(route('ventas.alumnos.buscar', ['q' => '74']))
            ->assertOk()
            ->assertExactJson([]);

        $this->getJson(route('ventas.alumnos.buscar', ['q' => '7412']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id_alumno', $propio->id_alumno);

        $this->getJson(route('ventas.alumnos.buscar', ['q' => 'Carlos']))
            ->assertOk()
            ->assertExactJson([]);
        $this->getJson(route('ventas.alumnos.buscar', ['q' => 'ALU987654']))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_productos_se_buscan_por_nombre_e_informan_si_tienen_stock_en_la_sede(): void
    {
        $sede = Sede::factory()->create();
        $otraSede = Sede::factory()->create();
        $local = User::factory()->create(['fksede' => $sede->id_sede]);
        $local->assignRole('Local');

        $disponible = Producto::factory()->create([
            'fksede' => $sede->id_sede,
            'prod_nombre' => 'Proteína Vainilla',
            'prod_estado' => true,
            'prod_cantidad' => 8,
        ]);
        $sinStock = Producto::factory()->create(['fksede' => $sede->id_sede, 'prod_nombre' => 'Proteína sin stock', 'prod_estado' => true, 'prod_cantidad' => 0]);
        Producto::factory()->create(['fksede' => $sede->id_sede, 'prod_nombre' => 'Proteína inactiva', 'prod_estado' => false, 'prod_cantidad' => 8]);
        Producto::factory()->create(['fksede' => $otraSede->id_sede, 'prod_nombre' => 'Proteína externa', 'prod_estado' => true, 'prod_cantidad' => 8]);

        $this->actingAs($local)->getJson(route('ventas.productos.buscar', ['q' => 'P']))
            ->assertOk()
            ->assertExactJson([]);

        $this->getJson(route('ventas.productos.buscar', ['q' => 'Proteína']))
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment([
                'id_productos' => $disponible->id_productos,
                'disponible' => true,
            ])
            ->assertJsonFragment([
                'id_productos' => $sinStock->id_productos,
                'disponible' => false,
            ])
            ->assertJsonMissing(['prod_nombre' => 'Proteína inactiva'])
            ->assertJsonMissing(['prod_nombre' => 'Proteína externa']);
    }

    public function test_membresias_se_buscan_por_nombre_y_excluyen_las_inactivas(): void
    {
        $local = User::factory()->create();
        $local->assignRole('Local');
        $activa = Membresia::factory()->create(['mem_nombre' => 'Plan Fuerza', 'estado' => 'A']);
        Membresia::factory()->create(['mem_nombre' => 'Plan Fuerza Antiguo', 'estado' => 'I']);

        $this->actingAs($local)->getJson(route('ventas.membresias.buscar', ['q' => 'P']))
            ->assertOk()
            ->assertExactJson([]);

        $this->getJson(route('ventas.membresias.buscar', ['q' => 'Fuerza']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id_mem', $activa->id_mem);
    }

    public function test_vencimiento_por_duracion_cuenta_el_inicio_como_primer_dia(): void
    {
        $servicio = app(MembresiaService::class);
        $inicio = Carbon::parse('2026-09-01');

        $this->assertSame('2026-09-01', $servicio->calcularFechaFin($inicio, 1, 'por_meses')->toDateString());
        $this->assertSame('2026-09-30', $servicio->calcularFechaFin($inicio, 30, 'por_meses')->toDateString());
        $this->assertSame('2026-11-29', $servicio->calcularFechaFin($inicio, 90, 'por_meses')->toDateString());
    }

    public function test_membresia_identificada_por_duracion_exige_fecha_de_inicio(): void
    {
        [$admin, $alumno, $metodo] = $this->prepararVenta();
        $membresia = Membresia::factory()->create(['mem_duracion' => 30]);

        $this->actingAs($admin)->post(route('ventas.store'), [
            'tipo_venta' => 'membresia',
            'fkalum' => $alumno->id_alumno,
            'fkmem' => $membresia->id_mem,
            'cobros' => [['fkmetodo' => $metodo->id_metod, 'monto' => $membresia->mem_precio]],
        ])->assertSessionHasErrors('fecha_inicio');
    }

    public function test_plan_fijo_guarda_su_rango_configurado(): void
    {
        [$admin, $alumno, $metodo] = $this->prepararVenta();
        $membresia = Membresia::factory()->create([
            'modalidad' => 'por_fechas',
            'fecha_inicio_fija' => '2026-10-05',
            'fecha_fin_fija' => '2026-12-20',
        ]);

        $this->actingAs($admin)->post(route('ventas.store'), [
            'tipo_venta' => 'membresia',
            'fkalum' => $alumno->id_alumno,
            'fkmem' => $membresia->id_mem,
            'fecha_inicio' => '2030-01-01',
            'fecha_fin' => '2030-12-31',
            'cobros' => [['fkmetodo' => $metodo->id_metod, 'monto' => $membresia->mem_precio]],
        ])->assertRedirect(route('ventas.index'));

        $this->assertDatabaseHas('membresias_alumno', [
            'fkalumno' => $alumno->id_alumno,
            'fkmem' => $membresia->id_mem,
            'fecha_inicio' => '2026-10-05',
            'fecha_fin' => '2026-12-20',
        ]);
    }

    public function test_plan_heredado_sin_rango_calcula_el_fin_e_ignora_el_enviado(): void
    {
        [$admin, $alumno, $metodo] = $this->prepararVenta();
        $membresia = Membresia::factory()->create([
            'modalidad' => 'por_fechas',
            'mem_duracion' => 30,
            'fecha_inicio_fija' => null,
            'fecha_fin_fija' => null,
        ]);

        $this->actingAs($admin)->post(route('ventas.store'), [
            'tipo_venta' => 'membresia',
            'fkalum' => $alumno->id_alumno,
            'fkmem' => $membresia->id_mem,
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2040-12-31',
            'cobros' => [['fkmetodo' => $metodo->id_metod, 'monto' => $membresia->mem_precio]],
        ])->assertRedirect(route('ventas.index'));

        $this->assertDatabaseHas('membresias_alumno', [
            'fkalumno' => $alumno->id_alumno,
            'fkmem' => $membresia->id_mem,
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-09-30',
        ]);
    }

    private function prepararVenta(): array
    {
        $sede = Sede::factory()->create();
        $admin = User::factory()->create(['fksede' => $sede->id_sede]);
        $admin->assignRole('Administrador');
        $this->abrirCajaPara($admin);

        return [
            $admin,
            Alumno::factory()->create(['fksede' => $sede->id_sede]),
            MetodoPago::factory()->create(),
        ];
    }
}
