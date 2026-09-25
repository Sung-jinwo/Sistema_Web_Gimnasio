<?php

namespace Tests\Feature\Ventas;

use App\Models\Abono;
use App\Models\Alumno;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Sede;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AbreCaja;
use Tests\TestCase;

class PagosCombinadosReservasTest extends TestCase
{
    use AbreCaja;
    use RefreshDatabase;

    private Sede $sede;

    private User $admin;

    private MetodoPago $efectivo;

    private MetodoPago $tarjeta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->sede = Sede::factory()->create();
        $this->admin = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->admin->assignRole('Administrador');
        $this->abrirCajaPara($this->admin, $this->sede->id_sede);
        $this->efectivo = MetodoPago::factory()->create(['metod_nombre' => 'Efectivo']);
        $this->tarjeta = MetodoPago::factory()->create(['metod_nombre' => 'Tarjeta']);
    }

    public function test_producto_admite_dos_cobros_reales_y_calcula_el_saldo_en_backend(): void
    {
        $alumno = Alumno::factory()->create(['fksede' => $this->sede->id_sede]);
        $producto = Producto::factory()->create(['fksede' => $this->sede->id_sede, 'prod_precio' => 100, 'prod_cantidad' => 5]);

        $this->actingAs($this->admin)->post(route('ventas.store'), [
            'tipo_venta' => 'producto', 'fkalum' => $alumno->id_alumno,
            'fkproducto' => $producto->id_productos, 'cantidad' => 1,
            'cobros' => [
                ['fkmetodo' => $this->efectivo->id_metod, 'monto' => 30],
                ['fkmetodo' => $this->tarjeta->id_metod, 'monto' => 70],
            ],
        ])->assertRedirect(route('ventas.index'));

        $venta = Venta::latest('id_venta')->firstOrFail();
        $this->assertSame($this->efectivo->id_metod, $venta->fkmetodo);
        $this->assertEquals(100, $venta->monto_pagado);
        $this->assertEquals(0, $venta->saldo);
        $this->assertNotNull($venta->pagada_at);
        $this->assertDatabaseHas('abonos', ['fkventa' => $venta->id_venta, 'fkmetodo' => $this->efectivo->id_metod, 'monto' => 30]);
        $this->assertDatabaseHas('abonos', ['fkventa' => $venta->id_venta, 'fkmetodo' => $this->tarjeta->id_metod, 'monto' => 70]);
    }

    public function test_rechaza_metodos_repetidos_y_suma_superior_al_total(): void
    {
        $alumno = Alumno::factory()->create(['fksede' => $this->sede->id_sede]);
        $producto = Producto::factory()->create(['fksede' => $this->sede->id_sede, 'prod_precio' => 50, 'prod_cantidad' => 5]);
        $base = ['tipo_venta' => 'producto', 'fkalum' => $alumno->id_alumno, 'fkproducto' => $producto->id_productos, 'cantidad' => 1];

        $this->actingAs($this->admin)->post(route('ventas.store'), $base + ['cobros' => [
            ['fkmetodo' => $this->efectivo->id_metod, 'monto' => 20],
            ['fkmetodo' => $this->efectivo->id_metod, 'monto' => 30],
        ]])->assertSessionHasErrors('cobros.1.fkmetodo');

        $this->post(route('ventas.store'), $base + ['cobros' => [
            ['fkmetodo' => $this->efectivo->id_metod, 'monto' => 30],
            ['fkmetodo' => $this->tarjeta->id_metod, 'monto' => 30],
        ]])->assertSessionHasErrors('error');
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_membresia_parcial_se_activa_y_pasa_a_cobranza_sin_ser_reserva(): void
    {
        $alumno = Alumno::factory()->create(['fksede' => $this->sede->id_sede]);
        $membresia = Membresia::factory()->create(['mem_precio' => 120, 'mem_duracion' => 30, 'comision' => 15]);

        $this->actingAs($this->admin)->post(route('ventas.store'), [
            'tipo_venta' => 'membresia', 'fkalum' => $alumno->id_alumno, 'fkmem' => $membresia->id_mem,
            'fecha_inicio' => today()->format('Y-m-d'), 'fecha_acordada' => today()->addDays(5)->format('Y-m-d'),
            'cobros' => [['fkmetodo' => $this->efectivo->id_metod, 'monto' => 20]],
        ])->assertRedirect(route('ventas.index'));

        $venta = Venta::latest('id_venta')->firstOrFail();
        $this->assertSame('completado', $venta->estado_venta);
        $this->assertSame('parcial', $venta->estado_pago);
        $this->assertDatabaseHas('membresias_alumno', ['fkventa' => $venta->id_venta, 'estado' => 'activa']);
        $this->actingAs($this->admin)->get(route('cobranza.index'))->assertOk()->assertSee('#'.$venta->id_venta);
    }

    public function test_membresia_permite_elegir_la_sede_donde_asistira(): void
    {
        $otraSede = Sede::factory()->create();
        $alumno = Alumno::factory()->create(['fksede' => $this->sede->id_sede]);
        $membresia = Membresia::factory()->create(['mem_precio' => 120, 'mem_duracion' => 30]);

        $this->actingAs($this->admin)->post(route('ventas.store'), [
            'tipo_venta' => 'membresia', 'fkalum' => $alumno->id_alumno, 'fkmem' => $membresia->id_mem,
            'fksede' => $otraSede->id_sede,
            'fecha_inicio' => today()->format('Y-m-d'),
            'cobros' => [['fkmetodo' => $this->efectivo->id_metod, 'monto' => 120]],
        ])->assertRedirect(route('ventas.index'));

        $venta = Venta::latest('id_venta')->firstOrFail();
        $this->assertEquals($otraSede->id_sede, $venta->fksede);
        $this->assertDatabaseHas('membresias_alumno', ['fkventa' => $venta->id_venta, 'estado' => 'activa']);
    }

    public function test_membresia_rechaza_sede_inexistente_y_usa_la_del_empleado_por_defecto(): void
    {
        $alumno = Alumno::factory()->create(['fksede' => $this->sede->id_sede]);
        $membresia = Membresia::factory()->create(['mem_precio' => 120, 'mem_duracion' => 30]);
        $base = [
            'tipo_venta' => 'membresia', 'fkalum' => $alumno->id_alumno, 'fkmem' => $membresia->id_mem,
            'fecha_inicio' => today()->format('Y-m-d'),
            'cobros' => [['fkmetodo' => $this->efectivo->id_metod, 'monto' => 120]],
        ];

        $this->actingAs($this->admin)->post(route('ventas.store'), $base + ['fksede' => 999999])
            ->assertSessionHasErrors('fksede');
        $this->assertDatabaseCount('ventas', 0);

        $this->actingAs($this->admin)->post(route('ventas.store'), $base)
            ->assertRedirect(route('ventas.index'));

        $this->assertEquals($this->sede->id_sede, Venta::latest('id_venta')->firstOrFail()->fksede);
    }

    public function test_venta_rapida_y_pase_diario_anonimo_exigen_pago_total(): void
    {
        $producto = Producto::factory()->create(['fksede' => $this->sede->id_sede, 'prod_precio' => 40, 'prod_cantidad' => 5]);
        $pase = Membresia::factory()->create(['mem_precio' => 30, 'mem_duracion' => 1]);
        $mensual = Membresia::factory()->create(['mem_precio' => 100, 'mem_duracion' => 30]);

        $this->actingAs($this->admin)->post(route('ventas.store'), [
            'tipo_venta' => 'rapida', 'fkproducto' => $producto->id_productos, 'cantidad' => 1,
            'cobros' => [['fkmetodo' => $this->efectivo->id_metod, 'monto' => 10]],
        ])->assertSessionHasErrors('error');

        $this->post(route('ventas.store'), [
            'tipo_venta' => 'membresia', 'fkmem' => $mensual->id_mem,
            'cobros' => [['fkmetodo' => $this->efectivo->id_metod, 'monto' => 100]],
        ])->assertSessionHasErrors('error');

        $this->post(route('ventas.store'), [
            'tipo_venta' => 'membresia', 'fkmem' => $pase->id_mem,
            'cobros' => [
                ['fkmetodo' => $this->efectivo->id_metod, 'monto' => 10],
                ['fkmetodo' => $this->tarjeta->id_metod, 'monto' => 20],
            ],
        ])->assertRedirect(route('ventas.index'));

        $venta = Venta::latest('id_venta')->firstOrFail();
        $this->assertNull($venta->fkalum);
        $this->assertSame($pase->id_mem, $venta->fkmem);
        $this->assertDatabaseMissing('membresias_alumno', ['fkventa' => $venta->id_venta]);
    }

    public function test_vencimiento_libera_stock_una_vez_y_penaliza_la_comision_fija(): void
    {
        $venta = $this->crearReservaProducto(2, 20);
        $venta->update(['fecha_acordada' => today()->subDays(14)]);
        $venta->cuotas()->update(['fecha_acordada' => today()->subDays(14)]);

        $this->artisan('ventas:procesar-vencimientos')->assertSuccessful();
        $this->assertEquals(10, $venta->detalles->first()->producto->fresh()->prod_cantidad);
        $this->assertSame('reserva_vencida', $venta->fresh()->estado_venta);
        $this->assertNotNull($venta->fresh()->stock_liberado_at);
        $this->assertEquals(40, $venta->comisiones()->first()->comision_base);
        $this->assertEquals(10, $venta->comisiones()->first()->penalizacion);

        $this->artisan('ventas:procesar-vencimientos')->assertSuccessful();
        $this->assertEquals(10, $venta->detalles->first()->producto->fresh()->prod_cantidad);
    }

    public function test_reserva_liberada_solo_la_cobra_admin_y_requiere_stock_para_el_pago_final(): void
    {
        $venta = $this->crearReservaProducto(2, 10);
        $venta->update(['fecha_acordada' => today()->subDay()]);
        $venta->cuotas()->update(['fecha_acordada' => today()->subDay()]);
        $this->artisan('ventas:procesar-vencimientos')->assertSuccessful();
        $producto = $venta->detalles->first()->producto;

        $local = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $local->assignRole('Local');
        $this->actingAs($local)->post(route('cobranza.abonar', $venta), [
            'monto' => $venta->saldo, 'fkmetodo' => $this->efectivo->id_metod,
        ])->assertForbidden();

        $producto->update(['prod_cantidad' => 1]);
        $this->actingAs($this->admin)->post(route('cobranza.abonar', $venta), [
            'monto' => $venta->saldo, 'fkmetodo' => $this->efectivo->id_metod,
        ])->assertSessionHasErrors('monto');
        $this->assertEquals(1, Abono::where('fkventa', $venta->id_venta)->count());

        $producto->update(['prod_cantidad' => 5]);
        $this->post(route('cobranza.abonar', $venta), [
            'monto' => $venta->fresh()->saldo, 'fkmetodo' => $this->tarjeta->id_metod,
        ])->assertRedirect();
        $this->assertEquals(3, $producto->fresh()->prod_cantidad);
        $this->assertSame('completado', $venta->fresh()->estado_venta);
        $this->assertEquals(0, $venta->fresh()->saldo);
    }

    public function test_anular_reserva_con_stock_liberado_no_duplica_existencias(): void
    {
        $venta = $this->crearReservaProducto(2, 10);
        $venta->update(['fecha_acordada' => today()->subDay()]);
        $venta->cuotas()->update(['fecha_acordada' => today()->subDay()]);
        $this->artisan('ventas:procesar-vencimientos')->assertSuccessful();
        $producto = $venta->detalles->first()->producto;
        $stock = $producto->fresh()->prod_cantidad;

        $this->actingAs($this->admin)->post(route('ventas.anular', $venta), ['motivo_anulacion' => 'Cliente desistió'])->assertRedirect(route('ventas.index'));
        $this->assertEquals($stock, $producto->fresh()->prod_cantidad);
    }

    public function test_comision_no_se_puede_liquidar_mientras_la_venta_tenga_saldo(): void
    {
        $venta = $this->crearReservaProducto(1, 12);
        $comision = $venta->comisiones()->firstOrFail();
        $this->assertSame('esperando_pago', $comision->estado);

        $this->actingAs($this->admin)->post(route('comisiones.liquidar', $comision), [
            'fkmetodo' => $this->efectivo->id_metod,
        ])->assertSessionHasErrors('error');

        $this->assertSame('esperando_pago', $comision->fresh()->estado);
        $this->assertNull($comision->fresh()->fecha_pago_real);
    }

    private function crearReservaProducto(int $cantidad, float $comision): Venta
    {
        $alumno = Alumno::factory()->create(['fksede' => $this->sede->id_sede]);
        $producto = Producto::factory()->create([
            'fksede' => $this->sede->id_sede, 'prod_precio' => 50,
            'prod_cantidad' => 10, 'comision' => $comision,
        ]);
        $this->actingAs($this->admin)->post(route('ventas.store'), [
            'tipo_venta' => 'producto', 'fkalum' => $alumno->id_alumno,
            'fkproducto' => $producto->id_productos, 'cantidad' => $cantidad,
            'fecha_acordada' => today()->addDay()->format('Y-m-d'),
            'cobros' => [['fkmetodo' => $this->efectivo->id_metod, 'monto' => 20]],
        ])->assertRedirect(route('ventas.index'));

        return Venta::with(['detalles.producto', 'comisiones', 'cuotas'])->latest('id_venta')->firstOrFail();
    }
}
