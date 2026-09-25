<?php

namespace Tests\Feature\Comisiones;

use App\Models\Alumno;
use App\Models\Caja;
use App\Models\Comision;
use App\Models\Gasto;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Sede;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AbreCaja;
use Tests\TestCase;

class FlujoRevisionTest extends TestCase
{
    use AbreCaja;
    use RefreshDatabase;

    private Sede $sede;

    private User $admin;

    private User $vendedor;

    private MetodoPago $efectivo;

    private MetodoPago $tarjeta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->sede = Sede::factory()->create();
        $this->admin = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->admin->assignRole('Administrador');
        $this->vendedor = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->vendedor->assignRole('Local');
        $this->efectivo = MetodoPago::factory()->create(['metod_nombre' => 'Efectivo']);
        $this->tarjeta = MetodoPago::factory()->create(['metod_nombre' => 'Tarjeta']);
        $this->abrirCajaPara($this->vendedor, $this->sede->id_sede);
        $this->abrirCajaPara($this->admin, $this->sede->id_sede);
    }

    private function venderMembresia(float $precio = 100, float $paga = 100, ?string $fechaAcordada = null): Venta
    {
        $alumno = Alumno::factory()->create(['fksede' => $this->sede->id_sede, 'fkuser' => $this->vendedor->id]);
        $plan = Membresia::factory()->create(['mem_precio' => $precio, 'mem_duracion' => 30, 'comision' => 100]);

        $payload = [
            'tipo_venta' => 'membresia', 'fkalum' => $alumno->id_alumno, 'fkmem' => $plan->id_mem,
            'fecha_inicio' => today()->format('Y-m-d'),
            'cobros' => [['fkmetodo' => $this->efectivo->id_metod, 'monto' => $paga]],
        ];
        if ($fechaAcordada) {
            $payload['fecha_acordada'] = $fechaAcordada;
        }

        $this->actingAs($this->vendedor)->post(route('ventas.store'), $payload)
            ->assertRedirect(route('ventas.index'));

        return Venta::latest('id_venta')->firstOrFail();
    }

    public function test_venta_completa_crea_comision_en_revision_en_su_caja(): void
    {
        $venta = $this->venderMembresia();
        $caja = Caja::where('fkuser', $this->vendedor->id)->where('estado', 'abierta')->firstOrFail();

        $comision = $venta->comisiones()->firstOrFail();
        $this->assertSame('pendiente_revision', $comision->estado);
        $this->assertEquals($caja->id_caja, $comision->fkcaja);
        $this->assertNotNull($comision->fecha_habilitacion);
    }

    public function test_venta_parcial_espera_y_ultimo_abono_habilita_y_congela(): void
    {
        $venta = $this->venderMembresia(100, 40, today()->format('Y-m-d'));
        $comision = $venta->comisiones()->firstOrFail();
        $this->assertSame('esperando_pago', $comision->estado);
        $this->assertNull($comision->fkcaja);

        // Simula mora de 10 días para verificar la penalización.
        $venta->update(['fecha_acordada' => today()->subDays(10)]);

        $this->actingAs($this->vendedor)->post(route('cobranza.abonar', $venta), [
            'monto' => 30, 'fkmetodo' => $this->efectivo->id_metod,
        ])->assertRedirect();
        $this->assertSame('esperando_pago', $comision->fresh()->estado);
        $this->assertEquals(5.0, (float) $comision->fresh()->penalizacion);

        $cajaAbono = Caja::where('fkuser', $this->vendedor->id)->where('estado', 'abierta')->firstOrFail();
        $this->actingAs($this->vendedor)->post(route('cobranza.abonar', $venta), [
            'monto' => 30, 'fkmetodo' => $this->tarjeta->id_metod,
        ])->assertRedirect();

        $comision->refresh();
        $this->assertSame('pendiente_revision', $comision->estado);
        $this->assertEquals($cajaAbono->id_caja, $comision->fkcaja);
        $this->assertEquals(5.0, (float) $comision->penalizacion);
        $this->assertEquals(95.0, (float) $comision->comision_final);
        $this->assertNotNull($comision->fecha_habilitacion);

        // Congelada: recálculos posteriores no la alteran.
        $venta->update(['fecha_acordada' => today()->subDays(60)]);
        app(\App\Services\CommissionService::class)->actualizarPenalizacionVenta($venta->fresh());
        $this->assertEquals(5.0, (float) $comision->fresh()->penalizacion);
    }

    public function test_caja_no_aprueba_con_bloqueantes_y_anulada_no_bloquea(): void
    {
        $venta = $this->venderMembresia();
        $caja = Caja::where('fkuser', $this->vendedor->id)->where('estado', 'abierta')->firstOrFail();
        $this->enviarCaja($caja);

        // Pendiente de revisión bloquea.
        $this->actingAs($this->admin)->post(route('caja.aprobar', $caja))
            ->assertSessionHasErrors('caja');
        $this->assertSame('pendiente_revision', $caja->fresh()->estado);

        // Observada también bloquea.
        $comision = $venta->comisiones()->firstOrFail();
        $this->actingAs($this->admin)->post(route('comisiones.observar', $comision), [
            'motivo_observacion' => 'Monto inconsistente',
        ])->assertRedirect();
        $this->actingAs($this->admin)->post(route('caja.aprobar', $caja))
            ->assertSessionHasErrors('caja');

        // Anulada se considera resuelta y ya no bloquea.
        $comision->update(['estado' => 'anulada']);
        $this->actingAs($this->admin)->post(route('caja.aprobar', $caja))
            ->assertRedirect(route('caja.index', ['caja' => $caja->id_caja]));
        $this->assertSame('cerrada', $caja->fresh()->estado);
    }

    public function test_caja_no_aprueba_con_gastos_pendientes(): void
    {
        $caja = Caja::where('fkuser', $this->vendedor->id)->where('estado', 'abierta')->firstOrFail();
        Gasto::create([
            'fksede' => $this->sede->id_sede, 'fkuser' => $this->vendedor->id, 'fkcaja' => $caja->id_caja,
            'gas_fecha' => today(), 'gas_concepto' => 'Pendiente', 'gas_monto' => 10,
            'fkmetodo' => $this->efectivo->id_metod, 'estado' => 'pendiente',
        ]);
        $this->enviarCaja($caja);

        $this->actingAs($this->admin)->post(route('caja.aprobar', $caja))
            ->assertSessionHasErrors('caja');
        $this->assertSame('pendiente_revision', $caja->fresh()->estado);
    }

    public function test_admin_aprueba_y_observa_con_motivo_sin_editar_importe(): void
    {
        $venta = $this->venderMembresia();
        $comision = $venta->comisiones()->firstOrFail();
        $base = (float) $comision->comision_base;
        $this->enviarCaja(Caja::where('fkuser', $this->vendedor->id)->where('estado', 'abierta')->firstOrFail());

        $this->actingAs($this->admin)->post(route('comisiones.observar', $comision), [])
            ->assertSessionHasErrors('motivo_observacion');

        $this->actingAs($this->admin)->post(route('comisiones.observar', $comision), [
            'motivo_observacion' => 'Revisar plan aplicado',
        ])->assertRedirect();
        $comision->refresh();
        $this->assertSame('observada', $comision->estado);
        $this->assertSame('Revisar plan aplicado', $comision->motivo_observacion);

        $this->actingAs($this->admin)->post(route('comisiones.aprobar', $comision))->assertRedirect();
        $comision->refresh();
        $this->assertSame('aprobada', $comision->estado);
        $this->assertEquals($base, (float) $comision->comision_final);
        $this->assertEquals($this->admin->id, $comision->aprobada_por);
        $this->assertNotNull($comision->fecha_aprobacion);
        $this->assertNull($comision->motivo_observacion);
    }

    public function test_liquidacion_exige_aprobada_metodo_y_mismo_empleado(): void
    {
        $venta = $this->venderMembresia();
        $comision = $venta->comisiones()->firstOrFail();
        $this->enviarCaja(Caja::where('fkuser', $this->vendedor->id)->where('estado', 'abierta')->firstOrFail());

        // Sin aprobar se rechaza aunque indique método.
        $this->actingAs($this->admin)->post(route('comisiones.liquidar', $comision), [
            'fkmetodo' => $this->efectivo->id_metod,
        ])->assertSessionHasErrors('error');
        $this->assertSame('pendiente_revision', $comision->fresh()->estado);

        // Aprobada exige método.
        $this->actingAs($this->admin)->post(route('comisiones.aprobar', $comision))->assertRedirect();
        $this->actingAs($this->admin)->post(route('comisiones.liquidar', $comision), [])
            ->assertSessionHasErrors('fkmetodo');

        $this->actingAs($this->admin)->post(route('comisiones.liquidar', $comision), [
            'fkmetodo' => $this->efectivo->id_metod, 'referencia' => 'OP-7', 'observacion' => 'Pago quincenal',
        ])->assertRedirect(route('comisiones.index'));

        $comision->refresh();
        $this->assertSame('liquidada', $comision->estado);
        $this->assertNotNull($comision->fecha_pago_real);
        $this->assertDatabaseHas('liquidaciones_comision', [
            'fkuser' => $this->vendedor->id,
            'total' => $comision->comision_final,
            'fkmetodo' => $this->efectivo->id_metod,
            'referencia' => 'OP-7',
        ]);

        // Liquidada ya no puede volver a liquidarse.
        $this->actingAs($this->admin)->post(route('comisiones.liquidar', $comision), [
            'fkmetodo' => $this->efectivo->id_metod,
        ])->assertSessionHasErrors('error');
    }

    public function test_liquidacion_por_lote_solo_aprobadas_del_mismo_empleado(): void
    {
        $this->venderMembresia();
        $this->venderMembresia();
        $this->enviarCaja(Caja::where('fkuser', $this->vendedor->id)->where('estado', 'abierta')->firstOrFail());
        $aprobadas = Comision::where('fkuser', $this->vendedor->id)->get();
        $this->assertCount(2, $aprobadas);

        // Lote con una no aprobada se rechaza íntegro.
        $this->actingAs($this->admin)->post(route('comisiones.liquidar-seleccion'), [
            'comisiones' => $aprobadas->pluck('id_comision')->all(),
            'fkmetodo' => $this->efectivo->id_metod,
        ])->assertSessionHasErrors('error');

        foreach ($aprobadas as $c) {
            $this->actingAs($this->admin)->post(route('comisiones.aprobar', $c))->assertRedirect();
        }

        $this->actingAs($this->admin)->post(route('comisiones.liquidar-seleccion'), [
            'comisiones' => $aprobadas->pluck('id_comision')->all(),
            'fkmetodo' => $this->tarjeta->id_metod,
            'referencia' => 'LOTE-1',
        ])->assertRedirect();

        $this->assertEquals(0, Comision::where('fkuser', $this->vendedor->id)->where('estado', 'aprobada')->count());
        $this->assertEquals(2, Comision::where('fkuser', $this->vendedor->id)->where('estado', 'liquidada')->count());
        $this->assertDatabaseHas('liquidaciones_comision', [
            'fkuser' => $this->vendedor->id, 'fkmetodo' => $this->tarjeta->id_metod, 'referencia' => 'LOTE-1',
        ]);
    }

    public function test_liquidacion_no_altera_caja_conciliada(): void
    {
        $venta = $this->venderMembresia();
        $comision = $venta->comisiones()->firstOrFail();
        $caja = Caja::where('fkuser', $this->vendedor->id)->where('estado', 'abierta')->firstOrFail();
        $this->enviarCaja($caja);
        $this->actingAs($this->admin)->post(route('comisiones.aprobar', $comision))->assertRedirect();

        $this->actingAs($this->admin)->post(route('caja.aprobar', $caja))
            ->assertRedirect(route('caja.index', ['caja' => $caja->id_caja]));

        $detallesAntes = $caja->fresh()->detallesCierre()->pluck('monto_declarado', 'fkmetodo')->all();
        $this->assertSame('cerrada', $caja->fresh()->estado);

        $this->actingAs($this->admin)->post(route('comisiones.liquidar', $comision), [
            'fkmetodo' => $this->efectivo->id_metod,
        ])->assertRedirect(route('comisiones.index'));

        $caja->refresh();
        $this->assertSame('cerrada', $caja->estado);
        $this->assertSame($detallesAntes, $caja->detallesCierre()->pluck('monto_declarado', 'fkmetodo')->all());
    }

    public function test_empleado_ve_solo_suyas_sin_controles(): void
    {
        $this->venderMembresia();
        $html = $this->actingAs($this->vendedor)->get(route('comisiones.mis-comisiones'))->assertOk()->getContent();

        $this->assertStringContainsString('Esperando cobro', $html);
        $this->assertStringContainsString('Aprobadas por cobrar', $html);
        $this->assertStringNotContainsString('Aprobar comisión', $html);
        $this->assertStringNotContainsString('Liquidar', $html);
        $this->assertStringNotContainsString('comisiones/aprobar', $html);
    }

    public function test_caja_abierta_no_permite_revisar_comisiones(): void
    {
        $venta = $this->venderMembresia();
        $comision = $venta->comisiones()->firstOrFail();

        $this->actingAs($this->admin)->post(route('comisiones.aprobar', $comision))
            ->assertSessionHasErrors('error');
        $this->actingAs($this->admin)->post(route('comisiones.observar', $comision), [
            'motivo_observacion' => 'X',
        ])->assertSessionHasErrors('error');
        $this->assertSame('pendiente_revision', $comision->fresh()->estado);
    }

    public function test_aprobacion_por_lote_audita_cada_comision(): void
    {
        $this->venderMembresia();
        $this->venderMembresia();
        $this->enviarCaja(Caja::where('fkuser', $this->vendedor->id)->where('estado', 'abierta')->firstOrFail());
        $ids = Comision::where('fkuser', $this->vendedor->id)->pluck('id_comision')->all();

        $this->actingAs($this->admin)->post(route('comisiones.aprobar-seleccion'), [
            'comisiones' => $ids,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertEquals(0, Comision::whereIn('id_comision', $ids)->where('estado', 'pendiente_revision')->count());
        foreach ($ids as $id) {
            $this->assertDatabaseHas('audit_logs', ['modelo' => 'Comision', 'modelo_id' => $id]);
        }
    }

    public function test_totales_excluyen_anuladas_y_reportes_clasifican(): void
    {
        $this->venderMembresia(100, 100);
        $anulada = Comision::where('fkuser', $this->vendedor->id)->firstOrFail();
        $anulada->update(['estado' => 'anulada']);

        $totales = app(\App\Services\ReportService::class)->reporteComisiones(null, null, $this->vendedor->id);
        $this->assertEquals(0.0, (float) $totales['total_final']);

        $html = $this->actingAs($this->admin)->get(route('reportes.comisiones'))->assertOk()->getContent();
        $this->assertStringContainsString('Anulada', $html);
        $this->assertStringNotContainsString('>Pendiente<', $html);
    }

    public function test_doble_aprobacion_concurrente_no_duplica_efectos(): void
    {
        $this->venderMembresia();
        $this->enviarCaja(Caja::where('fkuser', $this->vendedor->id)->where('estado', 'abierta')->firstOrFail());
        $comision = Comision::where('fkuser', $this->vendedor->id)->firstOrFail();

        $this->actingAs($this->admin)->post(route('comisiones.aprobar', $comision))->assertRedirect();
        $this->actingAs($this->admin)->post(route('comisiones.aprobar', $comision))
            ->assertSessionHasErrors('error');
        $this->assertSame('aprobada', $comision->fresh()->estado);
        $this->assertEquals(1, \App\Models\AuditLog::where('modelo', 'Comision')->where('modelo_id', $comision->id_comision)->count());
    }

    public function test_gasto_exige_metodo_y_flujo_de_aprobacion(): void
    {
        $this->actingAs($this->vendedor)->post(route('gastos.store'), [
        ])->assertSessionHasErrors('fkmetodo');

        $this->actingAs($this->vendedor)->post(route('gastos.store'), [
            'gas_fecha' => today()->format('Y-m-d'), 'gas_concepto' => 'Útiles', 'gas_monto' => 30,
            'fkmetodo' => $this->efectivo->id_metod,
        ])->assertRedirect(route('gastos.index'));

        $aprobado = Gasto::where('gas_concepto', 'Útiles')->firstOrFail();
        $this->assertSame('pendiente', $aprobado->estado);
        $this->assertEquals($this->efectivo->id_metod, $aprobado->fkmetodo);

        // Rechazo exige motivo.
        $this->actingAs($this->admin)->post(route('gastos.rechazar', $aprobado), [])
            ->assertSessionHasErrors('motivo_rechazo');

        $rechazado = Gasto::create([
            'fksede' => $this->sede->id_sede, 'fkuser' => $this->vendedor->id,
            'fkcaja' => Caja::where('fkuser', $this->vendedor->id)->where('estado', 'abierta')->firstOrFail()->id_caja,
            'gas_fecha' => today(), 'gas_concepto' => 'Rechazado', 'gas_monto' => 50,
            'fkmetodo' => $this->efectivo->id_metod, 'estado' => 'pendiente',
        ]);
        $this->actingAs($this->admin)->post(route('gastos.rechazar', $rechazado), [
            'motivo_rechazo' => 'No corresponde',
        ])->assertRedirect(route('gastos.index'));

        $this->actingAs($this->admin)->post(route('gastos.aprobar', $aprobado))
            ->assertRedirect(route('gastos.index'));

        // Aprobado descuenta del método correcto; rechazado no afecta.
        // Se verifica vía la conciliación pública del cierre.
        $cajaVendedor = Caja::where('fkuser', $this->vendedor->id)->where('estado', 'abierta')->firstOrFail();
        $this->enviarCaja($cajaVendedor);
        $detalles = $cajaVendedor->fresh()->detallesCierre()->pluck('monto_esperado', 'fkmetodo')->all();
        $this->assertEquals(-30.0, (float) $detalles[$this->efectivo->id_metod]);
        $this->assertEquals(0.0, (float) $detalles[$this->tarjeta->id_metod]);

        // Tras la decisión es inmutable para el empleado.
        $this->actingAs($this->vendedor)->put(route('gastos.update', $aprobado), [
            'gas_fecha' => today()->format('Y-m-d'), 'gas_concepto' => 'Cambiado', 'gas_monto' => 30,
        ])->assertForbidden();
    }

    private function enviarCaja(Caja $caja): void
    {
        $metodos = \App\Models\MetodoPago::orderBy('id_metod')->get();
        $declarados = [];
        foreach ($metodos as $metodo) {
            $declarados[$metodo->id_metod] = 0;
        }

        app(\App\Services\CashClosingService::class)->enviarCierre($caja->fresh(), $declarados);
    }
}
