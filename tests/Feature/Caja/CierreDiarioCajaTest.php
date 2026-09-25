<?php

namespace Tests\Feature\Caja;

use App\Models\Abono;
use App\Models\Caja;
use App\Models\Gasto;
use App\Models\MetodoPago;
use App\Models\Sede;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CierreDiarioCajaTest extends TestCase
{
    use RefreshDatabase;

    private Sede $sede;

    private User $admin;

    private User $local;

    private MetodoPago $efectivo;

    private MetodoPago $yape;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->sede = Sede::factory()->create();
        $this->admin = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->admin->assignRole('Administrador');
        $this->local = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->local->assignRole('Local');
        $this->efectivo = MetodoPago::factory()->create(['metod_nombre' => 'Efectivo', 'es_efectivo' => true]);
        $this->yape = MetodoPago::factory()->create(['metod_nombre' => 'Yape/Plin', 'es_efectivo' => false]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function crearCaja(User $usuario, string $estado, string $fechaOperativa, float $inicial = 0): Caja
    {
        return Caja::create([
            'fksede' => $this->sede->id_sede,
            'fkuser' => $usuario->id,
            'fecha_apertura' => Carbon::parse($fechaOperativa.' 08:00:00'),
            'fecha_operativa' => $fechaOperativa,
            'monto_inicial' => $inicial,
            'estado' => $estado,
        ]);
    }

    public function test_cola_muestra_pendientes_ordenados_por_antiguedad(): void
    {
        $ayer = today()->subDay()->format('Y-m-d');
        $hoy = today()->format('Y-m-d');
        $vieja = $this->crearCaja($this->local, 'pendiente_revision', $ayer, 10);
        $nueva = $this->crearCaja($this->local, 'pendiente_revision', $hoy, 20);
        $abierta = $this->crearCaja($this->local, 'abierta', $hoy, 30);

        // Por defecto solo pendientes de revisión, antiguos primero.
        $json = $this->actingAs($this->admin)->getJson(route('caja.index'))->assertOk()->json();
        $ids = collect($json['cajas']['data'])->pluck('id_caja')->all();
        $this->assertSame([$vieja->id_caja, $nueva->id_caja], $ids);

        $html = $this->actingAs($this->admin)->get(route('caja.index'))->assertOk()->getContent();
        $this->assertStringContainsString('>Hoy<', $html);
        $this->assertStringContainsString('>Ayer<', $html);

        // Filtro por fecha de hoy.
        $jsonHoy = $this->actingAs($this->admin)->getJson(route('caja.index', ['fecha' => $hoy]))->assertOk()->json();
        $this->assertSame([$nueva->id_caja], collect($jsonHoy['cajas']['data'])->pluck('id_caja')->all());
        $htmlHoy = $this->actingAs($this->admin)->get(route('caja.index', ['fecha' => $hoy]))->assertOk()->getContent();
        $this->assertStringContainsString('Ver pendientes', $htmlHoy);
    }

    public function test_caja_del_dia_anterior_bloquea_operaciones_y_nueva_apertura(): void
    {
        Carbon::setTestNow('2026-09-25 08:00:00');
        $caja = $this->caja('2026-09-24 09:00:00', 20);

        $this->actingAs($this->local)->post(route('gastos.store'), [
            'gas_concepto' => 'Limpieza',
            'gas_monto' => 10,
            'fkmetodo' => $this->efectivo->id_metod,
        ])->assertSessionHasErrors('error');

        $this->post(route('caja.apertura'), ['monto_inicial' => 10])
            ->assertSessionHasErrors('error');

        $this->assertSame('pendiente_cierre', $caja->fresh()->estado);
        $this->assertDatabaseCount('gastos', 0);
    }

    public function test_enviar_cierre_desbloquea_la_apertura_del_dia_siguiente_sin_aprobacion(): void
    {
        Carbon::setTestNow('2026-09-25 08:00:00');
        $caja = $this->caja('2026-09-24 09:00:00', 20);

        $this->actingAs($this->local)->post(route('caja.cierre', $caja), [
            'declarados' => [$this->efectivo->id_metod => 20, $this->yape->id_metod => 0],
        ])->assertRedirect(route('caja.index'));

        $this->assertSame('pendiente_revision', $caja->fresh()->estado);
        $this->post(route('caja.apertura'), ['monto_inicial' => 30])
            ->assertRedirect(route('caja.index'));

        $nueva = Caja::where('fkuser', $this->local->id)->where('estado', 'abierta')->firstOrFail();
        $this->assertSame('2026-09-25', $nueva->fecha_operativa->toDateString());
    }

    public function test_concilia_monto_inicial_abonos_y_gastos_por_metodo(): void
    {
        Carbon::setTestNow('2026-09-25 20:00:00');
        $caja = $this->caja('2026-09-25 08:00:00', 100);
        $venta = Venta::factory()->create([
            'fksede' => $this->sede->id_sede,
            'fkusers' => $this->local->id,
            'fkmetodo' => $this->efectivo->id_metod,
            'fkcaja' => $caja->id_caja,
        ]);
        $this->abono($venta, $caja, $this->efectivo, 50);
        $this->abono($venta, $caja, $this->yape, 30);
        $this->gasto($caja, $this->efectivo, 20, 'aprobado');
        $this->gasto($caja, $this->yape, 5, 'aprobado');

        $this->actingAs($this->local)->post(route('caja.cierre', $caja), [
            'declarados' => [$this->efectivo->id_metod => 125, $this->yape->id_metod => 25],
        ])->assertRedirect(route('caja.index'));

        $this->assertDatabaseHas('caja_cierre_detalles', [
            'fkcaja' => $caja->id_caja, 'fkmetodo' => $this->efectivo->id_metod,
            'monto_esperado' => 130, 'monto_declarado' => 125, 'diferencia' => 5,
        ]);
        $this->assertDatabaseHas('caja_cierre_detalles', [
            'fkcaja' => $caja->id_caja, 'fkmetodo' => $this->yape->id_metod,
            'monto_esperado' => 25, 'monto_declarado' => 25, 'diferencia' => 0,
        ]);
        $this->assertEquals(5, $caja->fresh()->diferencia);
    }

    public function test_admin_observa_empleado_reenvia_y_admin_aprueba(): void
    {
        Carbon::setTestNow('2026-09-25 20:00:00');
        $caja = $this->caja('2026-09-25 08:00:00', 50);
        $declarados = [$this->efectivo->id_metod => 40, $this->yape->id_metod => 0];

        $this->actingAs($this->local)->post(route('caja.cierre', $caja), compact('declarados'));
        $this->actingAs($this->admin)->post(route('caja.observar', $caja), [
            'observacion_revision' => 'Recontar efectivo.',
        ])->assertRedirect();
        $this->assertSame('observada', $caja->fresh()->estado);

        $this->actingAs($this->local)->post(route('caja.cierre', $caja), [
            'declarados' => [$this->efectivo->id_metod => 50, $this->yape->id_metod => 0],
        ])->assertRedirect(route('caja.index'));
        $this->actingAs($this->admin)->post(route('caja.aprobar', $caja), [
            'observacion_revision' => 'Conteo verificado.',
        ])->assertRedirect();

        $caja->refresh();
        $this->assertSame('cerrada', $caja->estado);
        $this->assertSame($this->admin->id, $caja->revisada_por);
        $this->assertSame('Conteo verificado.', $caja->observacion_revision);
    }

    public function test_empleado_no_ve_importes_esperados_antes_de_enviar(): void
    {
        Carbon::setTestNow('2026-09-25 20:00:00');
        $caja = $this->caja('2026-09-25 08:00:00', 50);

        $this->actingAs($this->local)->get(route('caja.index'))
            ->assertOk()
            ->assertSee('Cuenta y declara cada método sin consultar el esperado')
            ->assertDontSee('Total Esperado en Caja:');

        $this->actingAs($this->admin)->get(route('caja.index', ['caja' => $caja->id_caja]))
            ->assertOk()
            ->assertSee('Total Esperado en Caja:');
    }

    private function caja(string $apertura, float $montoInicial): Caja
    {
        return Caja::create([
            'fksede' => $this->sede->id_sede,
            'fkuser' => $this->local->id,
            'fecha_apertura' => Carbon::parse($apertura),
            'fecha_operativa' => Carbon::parse($apertura)->toDateString(),
            'monto_inicial' => $montoInicial,
            'estado' => 'abierta',
        ]);
    }

    private function abono(Venta $venta, Caja $caja, MetodoPago $metodo, float $monto): void
    {
        Abono::create([
            'fkventa' => $venta->id_venta,
            'fkmetodo' => $metodo->id_metod,
            'fksede' => $this->sede->id_sede,
            'fkcaja' => $caja->id_caja,
            'fkuser' => $this->local->id,
            'monto' => $monto,
            'fecha_abono' => now(),
        ]);
    }

    private function gasto(Caja $caja, MetodoPago $metodo, float $monto, string $estado): void
    {
        Gasto::create([
            'fksede' => $this->sede->id_sede,
            'fkuser' => $this->local->id,
            'fkcaja' => $caja->id_caja,
            'fkmetodo' => $metodo->id_metod,
            'gas_fecha' => today(),
            'gas_concepto' => 'Gasto '.$metodo->metod_nombre,
            'gas_monto' => $monto,
            'estado' => $estado,
        ]);
    }
}
