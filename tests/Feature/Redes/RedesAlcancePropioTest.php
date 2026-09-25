<?php

namespace Tests\Feature\Redes;

use App\Models\Alumno;
use App\Models\Comision;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Sede;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AbreCaja;
use Tests\TestCase;

class RedesAlcancePropioTest extends TestCase
{
    use AbreCaja;
    use RefreshDatabase;

    private Sede $sede;

    private User $redes;

    private User $otroRedes;

    private MetodoPago $metodo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->sede = Sede::factory()->create();
        $this->redes = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->redes->assignRole('Redes');
        $this->otroRedes = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->otroRedes->assignRole('Redes');
        $this->metodo = MetodoPago::factory()->create(['metod_nombre' => 'Efectivo', 'es_efectivo' => true]);
        $this->abrirCajaPara($this->redes, $this->sede->id_sede);
    }

    private function ventaDe(User $vendedor, float $total = 100): Venta
    {
        $alumno = Alumno::factory()->create(['fksede' => $this->sede->id_sede, 'fkuser' => $vendedor->id]);
        $membresia = Membresia::factory()->create(['mem_precio' => $total, 'mem_duracion' => 30]);

        $this->actingAs($vendedor)->post(route('ventas.store'), [
            'tipo_venta' => 'membresia', 'fkalum' => $alumno->id_alumno, 'fkmem' => $membresia->id_mem,
            'fecha_inicio' => today()->format('Y-m-d'),
            'cobros' => [['fkmetodo' => $this->metodo->id_metod, 'monto' => $total]],
        ])->assertRedirect(route('ventas.index'));

        return Venta::latest('id_venta')->firstOrFail();
    }

    public function test_redes_solo_ve_sus_propias_ventas(): void
    {
        $propia = $this->ventaDe($this->redes);
        $this->abrirCajaPara($this->otroRedes, $this->sede->id_sede);
        $ajena = $this->ventaDe($this->otroRedes);

        $html = $this->actingAs($this->redes)->get(route('ventas.index'))->assertOk()->getContent();

        $this->assertStringContainsString($propia->alumno->alum_nombre, $html);
        $this->assertStringNotContainsString($ajena->alumno->alum_nombre, $html);
    }

    public function test_redes_no_puede_cobrar_ni_ver_venta_ajena(): void
    {
        $this->abrirCajaPara($this->otroRedes, $this->sede->id_sede);
        $ajena = $this->ventaDe($this->otroRedes);
        $ajena->update(['monto_pagado' => 0, 'saldo' => $ajena->venta_total, 'estado_pago' => 'pendiente']);

        $this->actingAs($this->redes)->post(route('cobranza.abonar', $ajena), [
            'monto' => 10, 'fkmetodo' => $this->metodo->id_metod,
        ])->assertForbidden();
    }

    public function test_redes_ve_sus_comisiones_pero_no_las_ajenas_ni_el_indice(): void
    {
        $this->ventaDe($this->redes);
        $this->abrirCajaPara($this->otroRedes, $this->sede->id_sede);
        $this->ventaDe($this->otroRedes);

        $propia = Comision::where('fkuser', $this->redes->id)->firstOrFail();
        $ajena = Comision::where('fkuser', $this->otroRedes->id)->firstOrFail();

        $this->actingAs($this->redes)->get(route('comisiones.index'))->assertForbidden();
        $this->actingAs($this->redes)->get(route('comisiones.mis-comisiones'))->assertOk();
        $this->actingAs($this->redes)->get(route('comisiones.show', $propia))->assertOk();
        $this->actingAs($this->redes)->get(route('comisiones.show', $ajena))->assertForbidden();
    }

    public function test_redes_no_puede_cerrar_caja_ajena_y_si_la_propia(): void
    {
        $propia = \App\Models\Caja::where('fkuser', $this->redes->id)->where('estado', 'abierta')->firstOrFail();
        $ajena = $this->abrirCajaPara($this->otroRedes, $this->sede->id_sede);

        $this->actingAs($this->redes)->post(route('caja.cierre', $ajena), ['declarados' => [$this->metodo->id_metod => 0]])
            ->assertForbidden();

        $this->actingAs($this->redes)->post(route('caja.cierre', $propia), ['declarados' => [$this->metodo->id_metod => 0]])
            ->assertRedirect(route('caja.index'));
        $this->assertSame('pendiente_revision', $propia->fresh()->estado);
    }

    public function test_dashboard_redes_muestra_totales_propios(): void
    {
        $this->ventaDe($this->redes, 150);

        $html = $this->actingAs($this->redes)->get(route('dashboard.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Mis Ventas Hoy', $html);
        $this->assertStringContainsString('S/ 150.00', $html);
        $this->assertStringContainsString('Mi Comisión del Mes', $html);
        $this->assertStringContainsString('Mis ventas del mes', $html);
        $this->assertStringContainsString('data-grafico', $html);
    }
}
