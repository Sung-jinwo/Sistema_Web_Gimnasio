<?php

namespace Tests\Feature\Cobranza;

use App\Models\Alumno;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Sede;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AbreCaja;
use Tests\TestCase;

class CobranzaTest extends TestCase
{
    use AbreCaja;
    use RefreshDatabase;

    private User $admin;

    private Sede $sede;

    private MetodoPago $metodo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->sede = Sede::factory()->create();
        $this->admin = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->admin->assignRole('Administrador');
        $this->abrirCajaPara($this->admin, $this->sede->id_sede);
        $this->metodo = MetodoPago::factory()->create(['metod_nombre' => 'Efectivo']);
    }

    public function test_partial_sale_creates_initial_payment_and_is_settled_from_collections(): void
    {
        $alumno = Alumno::factory()->create(['fksede' => $this->sede->id_sede]);
        $producto = Producto::factory()->create([
            'fksede' => $this->sede->id_sede,
            'prod_precio' => 100,
            'prod_cantidad' => 5,
        ]);

        $this->actingAs($this->admin)->post(route('ventas.store'), [
            'tipo_venta' => 'producto',
            'fkalum' => $alumno->id_alumno,
            'fkproducto' => $producto->id_productos,
            'cantidad' => 1,
            'fkmetodo' => $this->metodo->id_metod,
            'monto_pagado' => 40,
            'fecha_acordada' => today()->addDay()->format('Y-m-d'),
        ])->assertRedirect(route('ventas.index'));

        $venta = Venta::latest('id_venta')->firstOrFail();
        $this->assertDatabaseHas('abonos', ['fkventa' => $venta->id_venta, 'monto' => 40]);
        $this->assertDatabaseHas('cuotas', ['fkventa' => $venta->id_venta, 'saldo' => 60, 'estado' => 'pendiente']);

        $this->post(route('cobranza.abonar', $venta), [
            'monto' => 60,
            'fkmetodo' => $this->metodo->id_metod,
            'fecha_abono' => today()->format('Y-m-d'),
        ])->assertRedirect();

        $venta->refresh();
        $this->assertSame('pagado', $venta->estado_pago);
        $this->assertEquals(0, $venta->saldo);
        $this->assertDatabaseHas('cuotas', ['fkventa' => $venta->id_venta, 'saldo' => 0, 'estado' => 'pagada']);
        $this->assertDatabaseCount('abonos', 2);
    }

    public function test_membership_sale_links_membership_assignment_and_initial_payment(): void
    {
        $alumno = Alumno::factory()->create(['fksede' => $this->sede->id_sede]);
        $membresia = Membresia::factory()->create(['mem_precio' => 150, 'mem_duracion' => 30]);

        $this->actingAs($this->admin)->post(route('ventas.store'), [
            'tipo_venta' => 'membresia',
            'fkalum' => $alumno->id_alumno,
            'fkmem' => $membresia->id_mem,
            'fkmetodo' => $this->metodo->id_metod,
            'monto_pagado' => 150,
            'fecha_inicio' => today()->format('Y-m-d'),
        ])->assertRedirect(route('ventas.index'));

        $venta = Venta::latest('id_venta')->firstOrFail();
        $this->assertDatabaseHas('membresias_alumno', [
            'fkventa' => $venta->id_venta,
            'fkalumno' => $alumno->id_alumno,
            'fkmem' => $membresia->id_mem,
        ]);
        $this->assertDatabaseHas('abonos', ['fkventa' => $venta->id_venta, 'monto' => 150]);
    }

    public function test_payment_cannot_exceed_balance(): void
    {
        $venta = Venta::factory()->create([
            'fksede' => $this->sede->id_sede,
            'fkusers' => $this->admin->id,
            'fkmetodo' => $this->metodo->id_metod,
            'venta_total' => 100,
            'monto_pagado' => 20,
            'saldo' => 80,
            'estado_pago' => 'parcial',
            'estado_venta' => 'completado',
        ]);

        $this->actingAs($this->admin)->from(route('cobranza.index'))->post(route('cobranza.abonar', $venta), [
            'monto' => 81,
            'fkmetodo' => $this->metodo->id_metod,
        ])->assertRedirect(route('cobranza.index'))->assertSessionHasErrors('monto');

        $this->assertDatabaseCount('abonos', 0);
        $this->assertEquals(80, $venta->fresh()->saldo);
    }

    public function test_local_user_cannot_collect_sale_from_another_location(): void
    {
        $otraSede = Sede::factory()->create();
        $local = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $local->assignRole('Local');
        $venta = Venta::factory()->create([
            'fksede' => $otraSede->id_sede,
            'venta_total' => 100,
            'monto_pagado' => 0,
            'saldo' => 100,
            'estado_pago' => 'pendiente',
        ]);

        $this->actingAs($local)->post(route('cobranza.abonar', $venta), [
            'monto' => 20,
            'fkmetodo' => $this->metodo->id_metod,
        ])->assertForbidden();

        $this->assertDatabaseCount('abonos', 0);
    }

    public function test_legacy_pages_redirect_and_users_without_permission_cannot_open_collections(): void
    {
        $this->actingAs($this->admin)->get('/pagos-incompletos')->assertRedirect('/cobranza');

        $asistencia = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $asistencia->assignRole('Asistencia');

        $this->actingAs($asistencia)->get(route('cobranza.index'))->assertForbidden();
    }
}
