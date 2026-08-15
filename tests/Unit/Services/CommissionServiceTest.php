<?php

namespace Tests\Unit\Services;

use App\Models\Alumno;
use App\Models\Membresia;
use App\Models\MembresiaAlumno;
use App\Models\Producto;
use App\Models\Sede;
use App\Models\Venta;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CommissionService $commissionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->commissionService = new CommissionService(
            app(\App\Services\PenaltyService::class)
        );
    }

    public function test_calculates_commission_for_product_sale(): void
    {
        $sede = Sede::factory()->create();
        $producto = Producto::factory()->create([
            'fksede' => $sede->id_sede,
            'prod_precio' => 100.00,
        ]);
        $venta = Venta::factory()->create([
            'tipo_venta' => 'producto',
            'fkproducto' => $producto->id_productos,
            'venta_total' => 100.00,
        ]);

        $comision = $this->commissionService->calcularComisionBase($venta->id_venta, 1);

        $this->assertEquals(10.00, $comision);
    }

    public function test_calculates_commission_for_membership_sale(): void
    {
        $sede = Sede::factory()->create();
        $alumno = Alumno::factory()->create(['fksede' => $sede->id_sede]);
        $membresia = Membresia::factory()->create(['comision' => 15.00]);

        MembresiaAlumno::create([
            'fkalumno' => $alumno->id_alumno,
            'fkmem' => $membresia->id_mem,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addMonth(),
            'precio_vendido' => $membresia->mem_precio,
            'comision_aplicada' => 15.00,
            'modalidad' => 'por_meses',
            'estado' => 'activa',
        ]);

        $venta = Venta::factory()->create([
            'tipo_venta' => 'membresia',
            'fkalum' => $alumno->id_alumno,
            'venta_total' => $membresia->mem_precio,
        ]);

        $comision = $this->commissionService->calcularComisionBase($venta->id_venta, 1);

        $this->assertEquals(15.00, $comision);
    }

    public function test_saves_commission_correctly(): void
    {
        $venta = Venta::factory()->create([
            'tipo_venta' => 'producto',
            'venta_total' => 100.00,
        ]);

        $comision = $this->commissionService->guardarComision(
            $venta->id_venta,
            1,
            10.00,
            null
        );

        $this->assertDatabaseHas('comisiones', [
            'fkventa' => $venta->id_venta,
            'fkuser' => 1,
            'comision_base' => 10.00,
            'comision_final' => 10.00,
            'estado' => 'pendiente',
        ]);
    }

    public function test_registers_payment_and_applies_penalty(): void
    {
        $venta = Venta::factory()->create();
        $comision = \App\Models\Comision::create([
            'fkventa' => $venta->id_venta,
            'fkuser' => 1,
            'comision_base' => 100.00,
            'penalizacion' => 0,
            'comision_final' => 100.00,
            'fecha_acordada_pago' => now()->subDays(20),
            'fecha_pago_real' => null,
            'tipo' => 'venta',
            'estado' => 'pendiente',
        ]);

        $comisionActualizada = $this->commissionService->registrarPagoComision($comision->id_comision);

        $this->assertEquals('liquidada', $comisionActualizada->estado);
        $this->assertEquals(now()->format('Y-m-d'), $comisionActualizada->fecha_pago_real);
        $this->assertGreaterThan(0, $comisionActualizada->penalizacion);
        $this->assertLessThan(100.00, $comisionActualizada->comision_final);
    }

    public function test_gets_commissions_by_cash_register(): void
    {
        $venta1 = Venta::factory()->create();
        $venta2 = Venta::factory()->create();
        $caja = \App\Models\Caja::create(['fksede' => $venta1->fksede, 'fkuser' => $venta1->fkusers, 'fecha_apertura' => now(), 'monto_inicial' => 0, 'estado' => 'abierta']);

        \App\Models\Comision::create([
            'fkventa' => $venta1->id_venta,
            'fkcaja' => $caja->id_caja,
            'fkuser' => $venta1->fkusers,
            'comision_base' => 10.00,
            'penalizacion' => 0,
            'comision_final' => 10.00,
            'tipo' => 'venta',
            'estado' => 'pendiente',
        ]);

        \App\Models\Comision::create([
            'fkventa' => $venta2->id_venta,
            'fkcaja' => $caja->id_caja,
            'fkuser' => $venta2->fkusers,
            'comision_base' => 20.00,
            'penalizacion' => 5.00,
            'comision_final' => 15.00,
            'tipo' => 'venta',
            'estado' => 'pendiente',
        ]);

        $resultado = $this->commissionService->obtenerComisionesPorCaja($caja->id_caja);

        $this->assertEquals(2, $resultado['cantidad']);
        $this->assertEquals(30.00, $resultado['total_base']);
        $this->assertEquals(5.00, $resultado['total_penalizaciones']);
        $this->assertEquals(25.00, $resultado['total_final']);
    }
}
