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
        $this->commissionService = app(CommissionService::class);
    }

    public function test_calculates_commission_for_product_sale(): void
    {
        $sede = Sede::factory()->create();
        $producto = Producto::factory()->create([
            'fksede' => $sede->id_sede,
            'prod_precio' => 100.00,
            'comision' => 10.00,
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
            'fkmem' => $membresia->id_mem,
            'venta_total' => $membresia->mem_precio,
        ]);

        $comision = $this->commissionService->calcularComisionBase($venta->id_venta, 1);

        $this->assertEquals(15.00, $comision);
    }

    public function test_saves_commission_correctly(): void
    {
        $impaga = Venta::factory()->create([
            'tipo_venta' => 'producto',
            'venta_total' => 100.00,
            'monto_pagado' => 40.00,
            'saldo' => 60.00,
        ]);

        $enEspera = $this->commissionService->guardarComision(
            $impaga->id_venta,
            1,
            10.00,
            null
        );

        $this->assertSame('esperando_pago', $enEspera->estado);
        $this->assertNull($enEspera->fkcaja);
        $this->assertDatabaseHas('comisiones', [
            'fkventa' => $impaga->id_venta,
            'fkuser' => 1,
            'comision_base' => 10.00,
            'comision_final' => 10.00,
            'estado' => 'esperando_pago',
        ]);

        $caja = \App\Models\Caja::create([
            'fksede' => $impaga->fksede, 'fkuser' => $impaga->fkusers,
            'fecha_apertura' => now(), 'monto_inicial' => 0, 'estado' => 'abierta',
        ]);
        $pagada = Venta::factory()->create([
            'tipo_venta' => 'producto',
            'venta_total' => 100.00,
            'monto_pagado' => 100.00,
            'saldo' => 0,
            'fkcaja' => $caja->id_caja,
        ]);

        $habilitada = $this->commissionService->guardarComision($pagada->id_venta, 1, 10.00, null);

        $this->assertSame('pendiente_revision', $habilitada->estado);
        $this->assertEquals($caja->id_caja, $habilitada->fkcaja);
        $this->assertNotNull($habilitada->fecha_habilitacion);
    }

    public function test_registers_payment_only_for_aprobada_with_metodo(): void
    {
        $admin = \App\Models\User::factory()->create();
        $this->actingAs($admin);
        $metodo = \App\Models\MetodoPago::factory()->create();
        $venta = Venta::factory()->create([
            'saldo' => 0,
            'estado_pago' => 'pagado',
            'fecha_acordada' => now()->subDays(20),
            'pagada_at' => now(),
        ]);
        $comision = \App\Models\Comision::create([
            'fkventa' => $venta->id_venta,
            'fkuser' => 1,
            'comision_base' => 100.00,
            'penalizacion' => 10.00,
            'comision_final' => 90.00,
            'fecha_acordada_pago' => now()->subDays(20),
            'fecha_pago_real' => null,
            'tipo' => 'venta',
            'estado' => 'aprobada',
        ]);

        $comisionActualizada = $this->commissionService->registrarPagoComision($comision->id_comision, [
            'fkmetodo' => $metodo->id_metod,
            'referencia' => 'OP-001',
        ]);

        $this->assertEquals('liquidada', $comisionActualizada->estado);
        $this->assertEquals(now()->format('Y-m-d'), $comisionActualizada->fecha_pago_real);
        $this->assertEquals(10.00, $comisionActualizada->penalizacion);
        $this->assertEquals(90.00, $comisionActualizada->comision_final);
        $this->assertDatabaseHas('liquidaciones_comision', [
            'fkuser' => 1,
            'total' => 90.00,
            'fkmetodo' => $metodo->id_metod,
            'referencia' => 'OP-001',
        ]);
    }

    public function test_rejects_liquidation_of_non_aprobada(): void
    {
        $admin = \App\Models\User::factory()->create();
        $this->actingAs($admin);
        $comision = \App\Models\Comision::create([
            'fkventa' => null,
            'fkuser' => 1,
            'comision_base' => 50.00,
            'penalizacion' => 0,
            'comision_final' => 50.00,
            'tipo' => 'venta',
            'estado' => 'pendiente_revision',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->commissionService->registrarPagoComision($comision->id_comision, [
            'fkmetodo' => \App\Models\MetodoPago::factory()->create()->id_metod,
        ]);
    }

    public function test_penalty_freezes_after_aprobacion(): void
    {
        $venta = Venta::factory()->create([
            'saldo' => 0,
            'estado_pago' => 'pagado',
            'fecha_acordada' => now()->subDays(20),
            'pagada_at' => now(),
        ]);
        $comision = \App\Models\Comision::create([
            'fkventa' => $venta->id_venta,
            'fkuser' => 1,
            'comision_base' => 100.00,
            'penalizacion' => 0,
            'comision_final' => 100.00,
            'fecha_acordada_pago' => now()->subDays(20),
            'tipo' => 'venta',
            'estado' => 'esperando_pago',
        ]);

        $this->commissionService->actualizarPenalizacionVenta($venta);
        $this->assertGreaterThan(0, $comision->fresh()->penalizacion);

        $congelada = $comision->fresh()->penalizacion;
        $comision->update(['estado' => 'pendiente_revision', 'fecha_habilitacion' => now()]);
        $admin = \App\Models\User::factory()->create();
        $this->commissionService->aprobarComision($comision->id_comision, $admin->id);

        $venta->update(['fecha_acordada' => now()->subDays(60)]);
        $this->commissionService->actualizarPenalizacionVenta($venta->fresh());

        $this->assertEquals($congelada, $comision->fresh()->penalizacion);
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
            'estado' => 'pendiente_revision',
        ]);

        \App\Models\Comision::create([
            'fkventa' => $venta2->id_venta,
            'fkcaja' => $caja->id_caja,
            'fkuser' => $venta2->fkusers,
            'comision_base' => 20.00,
            'penalizacion' => 5.00,
            'comision_final' => 15.00,
            'tipo' => 'venta',
            'estado' => 'pendiente_revision',
        ]);

        $resultado = $this->commissionService->obtenerComisionesPorCaja($caja->id_caja);

        $this->assertEquals(2, $resultado['cantidad']);
        $this->assertEquals(30.00, $resultado['total_base']);
        $this->assertEquals(5.00, $resultado['total_penalizaciones']);
        $this->assertEquals(25.00, $resultado['total_final']);
    }
}
