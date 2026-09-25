<?php

namespace Tests\Feature\Caja;

use App\Models\Caja;
use App\Models\Gasto;
use App\Models\MetodoPago;
use App\Models\Sede;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AbreCaja;
use Tests\TestCase;

class CajaPorEmpleadoTest extends TestCase
{
    use AbreCaja;
    use RefreshDatabase;

    private Sede $sede;

    private User $admin;

    private User $local1;

    private User $local2;

    private MetodoPago $metodo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->sede = Sede::factory()->create();
        $this->admin = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->admin->assignRole('Administrador');
        $this->local1 = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->local1->assignRole('Local');
        $this->local2 = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->local2->assignRole('Local');
        $this->metodo = MetodoPago::factory()->create(['metod_nombre' => 'Efectivo', 'es_efectivo' => true]);
    }

    public function test_dos_empleados_pueden_tener_cajas_abiertas_simultaneas(): void
    {
        $this->actingAs($this->local1)->post(route('caja.apertura'), ['monto_inicial' => 100])
            ->assertRedirect(route('caja.index'));

        $this->actingAs($this->local2)->post(route('caja.apertura'), ['monto_inicial' => 50])
            ->assertRedirect(route('caja.index'));

        $this->assertEquals(2, Caja::where('fksede', $this->sede->id_sede)->where('estado', 'abierta')->count());
    }

    public function test_empleado_no_puede_abrir_segunda_caja_propia(): void
    {
        $this->abrirCajaPara($this->local1, $this->sede->id_sede);

        $this->actingAs($this->local1)->post(route('caja.apertura'), ['monto_inicial' => 10])
            ->assertSessionHasErrors('error');

        $this->assertEquals(1, Caja::where('fkuser', $this->local1->id)->where('estado', 'abierta')->count());
    }

    public function test_local_solo_ve_sus_propias_cajas(): void
    {
        $this->abrirCajaPara($this->local1, $this->sede->id_sede);
        $this->abrirCajaPara($this->local2, $this->sede->id_sede);

        $respuesta = $this->actingAs($this->local1)->getJson(route('caja.index'))->assertOk();
        $ids = collect($respuesta->json('cajas.data'))->pluck('fkuser')->all();

        $this->assertNotEmpty($ids);
        foreach ($ids as $fkuser) {
            $this->assertEquals($this->local1->id, $fkuser);
        }
    }

    public function test_local_no_puede_cerrar_ni_descargar_caja_ajena(): void
    {
        $ajena = $this->abrirCajaPara($this->local2, $this->sede->id_sede);

        $this->actingAs($this->local1)->post(route('caja.cierre', $ajena), ['declarados' => [$this->metodo->id_metod => 0]])
            ->assertForbidden();

        $cerrada = $this->abrirCajaPara($this->local2, $this->sede->id_sede);
        $cerrada->update(['estado' => 'cerrada', 'fecha_cierre' => now()]);

        $this->actingAs($this->local1)->get(route('caja.pdf', $cerrada))->assertForbidden();
        $this->assertSame('cerrada', $cerrada->fresh()->estado);
    }

    public function test_admin_puede_cerrar_caja_ajena_en_representacion(): void
    {
        $ajena = $this->abrirCajaPara($this->local1, $this->sede->id_sede);

        $this->actingAs($this->admin)->post(route('caja.cierre', $ajena), [
            'declarados' => [$this->metodo->id_metod => 100],
        ])->assertSessionHasErrors('observacion');

        $this->actingAs($this->admin)->post(route('caja.cierre', $ajena), [
            'declarados' => [$this->metodo->id_metod => 100],
            'observacion' => 'Empleado ausente al finalizar el turno.',
        ])
            ->assertRedirect(route('caja.index'));
        $this->assertSame('pendiente_revision', $ajena->fresh()->estado);
        $this->assertSame('Empleado ausente al finalizar el turno.', $ajena->fresh()->observacion);

        $this->post(route('caja.aprobar', $ajena))->assertRedirect();

        $this->assertSame('cerrada', $ajena->fresh()->estado);
    }

    public function test_admin_puede_abrir_su_caja_mientras_supervisa_una_caja_ajena(): void
    {
        $ajena = $this->abrirCajaPara($this->local1, $this->sede->id_sede);

        $respuesta = $this->actingAs($this->admin)->get(route('caja.index', ['caja' => $ajena->id_caja]));

        $respuesta->assertOk()
            ->assertSee('Abrir Caja')
            ->assertSee($this->local1->name);

        $this->post(route('caja.apertura'), [
            'fksede' => $this->sede->id_sede,
            'monto_inicial' => 20,
        ])->assertRedirect(route('caja.index'));

        $this->assertDatabaseHas('cajas', [
            'fkuser' => $this->admin->id,
            'estado' => 'abierta',
        ]);
        $this->assertSame('abierta', $ajena->fresh()->estado);
    }

    public function test_ventas_y_gastos_muestran_aviso_y_bloquean_botones_sin_caja(): void
    {
        $this->actingAs($this->local1)->get(route('ventas.index'))
            ->assertOk()
            ->assertSee('Debes abrir tu caja antes de registrar ventas.')
            ->assertSee('Ir a Caja')
            ->assertSee('disabled', false);

        $this->get(route('gastos.index'))
            ->assertOk()
            ->assertSee('Debes abrir tu caja antes de registrar gastos.')
            ->assertSee('Ir a Caja')
            ->assertSee('disabled', false);
    }

    public function test_error_de_caja_es_visible_y_reabre_el_modal_de_gasto(): void
    {
        $respuesta = $this->actingAs($this->local1)->from(route('gastos.index'))->post(route('gastos.store'), [
            '_formulario' => 'gasto',
            'gas_concepto' => 'Útiles de oficina',
            'gas_monto' => 15,
            'fkmetodo' => $this->metodo->id_metod,
        ]);

        $respuesta->assertRedirect(route('gastos.index'))
            ->assertSessionHasErrors('error');

        $this->get(route('gastos.index'))
            ->assertOk()
            ->assertSee('Debes aperturar tu caja antes de registrar un gasto.')
            ->assertSee('showRegistrarModal: true', false)
            ->assertSee('Útiles de oficina');
    }

    public function test_se_puede_enviar_cierre_con_gastos_pendientes_pero_no_aprobarlo(): void
    {
        $caja = $this->abrirCajaPara($this->local1, $this->sede->id_sede);
        Gasto::create([
            'fksede' => $this->sede->id_sede,
            'fkuser' => $this->local1->id,
            'fkcaja' => $caja->id_caja,
            'gas_fecha' => today(),
            'gas_concepto' => 'Compra pendiente',
            'gas_monto' => 20,
            'fkmetodo' => $this->metodo->id_metod,
            'estado' => 'pendiente',
        ]);

        $this->actingAs($this->local1)->post(route('caja.cierre', $caja), [
            'declarados' => [$this->metodo->id_metod => 0],
        ])->assertRedirect(route('caja.index'));

        $this->assertSame('pendiente_revision', $caja->fresh()->estado);

        $this->actingAs($this->admin)->post(route('caja.aprobar', $caja))
            ->assertSessionHasErrors('caja');
        $this->assertSame('pendiente_revision', $caja->fresh()->estado);
    }

    public function test_venta_se_rechaza_sin_caja_abierta_y_asigna_fkcaja_con_caja(): void
    {
        $alumno = \App\Models\Alumno::factory()->create(['fksede' => $this->sede->id_sede]);
        $producto = \App\Models\Producto::factory()->create([
            'fksede' => $this->sede->id_sede, 'prod_precio' => 50, 'prod_cantidad' => 5,
        ]);
        $payload = [
            'tipo_venta' => 'producto', 'fkalum' => $alumno->id_alumno,
            'fkproducto' => $producto->id_productos, 'cantidad' => 1,
            'cobros' => [['fkmetodo' => $this->metodo->id_metod, 'monto' => 50]],
        ];

        $this->actingAs($this->local1)->from(route('ventas.index'))->post(route('ventas.store'), $payload)
            ->assertRedirect(route('ventas.index'))
            ->assertSessionHasErrors('error');
        $this->assertDatabaseCount('ventas', 0);

        $this->get(route('ventas.index'))
            ->assertOk()
            ->assertSee('Debes aperturar tu caja antes de registrar una venta.')
            ->assertSee('modalProductos: true', false);

        $caja = $this->abrirCajaPara($this->local1, $this->sede->id_sede);

        $this->actingAs($this->local1)->post(route('ventas.store'), $payload)
            ->assertRedirect(route('ventas.index'));

        $venta = Venta::latest('id_venta')->firstOrFail();
        $this->assertEquals($caja->id_caja, $venta->fkcaja);
        $this->assertEquals($caja->id_caja, $venta->abonos()->first()->fkcaja);
    }

    public function test_abono_y_gasto_se_rechazan_sin_caja_abierta(): void
    {
        $venta = Venta::factory()->create([
            'fksede' => $this->sede->id_sede, 'fkusers' => $this->local1->id,
            'fkmetodo' => $this->metodo->id_metod, 'venta_total' => 100,
            'monto_pagado' => 0, 'saldo' => 100, 'estado_pago' => 'pendiente',
            'estado_venta' => 'completado',
        ]);

        $this->actingAs($this->local1)->post(route('cobranza.abonar', $venta), [
            'monto' => 10, 'fkmetodo' => $this->metodo->id_metod,
        ])->assertStatus(422);
        $this->assertDatabaseCount('abonos', 0);

        $this->actingAs($this->local1)->post(route('gastos.store'), [
            'fkcategoria' => null, 'gas_fecha' => today()->format('Y-m-d'),
            'gas_concepto' => 'Útiles', 'gas_monto' => 15, 'fkmetodo' => $this->metodo->id_metod,
        ])->assertSessionHasErrors('error');
        $this->assertDatabaseCount('gastos', 0);
    }

    public function test_local_recibe_403_en_comisiones_y_reportes(): void
    {
        $this->actingAs($this->local1)->get(route('comisiones.index'))->assertForbidden();
        $this->actingAs($this->local1)->get(route('comisiones.mis-comisiones'))->assertOk();
        $this->actingAs($this->local1)->get(route('reportes.index'))->assertForbidden();
        $this->actingAs($this->local1)->get(route('reportes.ventas'))->assertForbidden();
        $this->actingAs($this->local1)->get(route('reportes.caja'))->assertForbidden();

        $this->actingAs($this->admin)->get(route('comisiones.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('reportes.index'))->assertOk();
    }

    public function test_local_ve_sus_comisiones_y_volver_no_da_403(): void
    {
        $alumno = \App\Models\Alumno::factory()->create(['fksede' => $this->sede->id_sede]);
        $membresia = \App\Models\Membresia::factory()->create(['mem_precio' => 100, 'mem_duracion' => 30]);
        $this->abrirCajaPara($this->local1, $this->sede->id_sede);

        $this->actingAs($this->local1)->post(route('ventas.store'), [
            'tipo_venta' => 'membresia', 'fkalum' => $alumno->id_alumno, 'fkmem' => $membresia->id_mem,
            'fecha_inicio' => today()->format('Y-m-d'),
            'cobros' => [['fkmetodo' => $this->metodo->id_metod, 'monto' => 100]],
        ])->assertRedirect(route('ventas.index'));

        $propia = \App\Models\Comision::where('fkuser', $this->local1->id)->firstOrFail();

        $this->actingAs($this->local1)->get(route('comisiones.show', $propia))->assertOk()
            ->assertSee(route('comisiones.mis-comisiones'), false);

        $ajena = \App\Models\Comision::create([
            'fkuser' => $this->local2->id, 'fkventa' => null, 'monto' => 10,
            'comision_base' => 10, 'penalizacion' => 0, 'comision_final' => 10,
            'tipo' => 'venta', 'estado' => 'pendiente_revision',
        ]);
        $this->actingAs($this->local1)->get(route('comisiones.show', $ajena))->assertForbidden();
    }
}
