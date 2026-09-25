<?php

namespace Tests\Feature\Membresias;

use App\Models\Alumno;
use App\Models\Membresia;
use App\Models\MembresiaAlumno;
use App\Models\Sede;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CongelamientoTest extends TestCase
{
    use RefreshDatabase;

    private Sede $sede;

    private User $admin;

    private User $local;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->sede = Sede::factory()->create();
        $this->admin = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->admin->assignRole('Administrador');
        $this->local = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->local->assignRole('Local');
    }

    private function membresiaVigente(bool $permiteCongelar = true, int $diasRestantes = 30): MembresiaAlumno
    {
        $alumno = Alumno::factory()->create(['fksede' => $this->sede->id_sede, 'fkuser' => $this->admin->id]);
        $plan = Membresia::factory()->create(['mem_duracion' => 30, 'permite_congelamiento' => $permiteCongelar]);

        return MembresiaAlumno::create([
            'fkalumno' => $alumno->id_alumno,
            'fkmem' => $plan->id_mem,
            'fecha_inicio' => today()->subDays(5)->format('Y-m-d'),
            'fecha_fin' => today()->addDays($diasRestantes)->format('Y-m-d'),
            'precio_vendido' => $plan->mem_precio,
            'estado' => 'activa',
        ]);
    }

    public function test_plan_permite_congelamiento_sin_importar_duracion(): void
    {
        $corta = Membresia::factory()->create(['mem_duracion' => 1, 'permite_congelamiento' => true]);
        $larga = Membresia::factory()->create(['mem_duracion' => 365, 'permite_congelamiento' => false]);

        $this->assertTrue((bool) $corta->permite_congelamiento);
        $this->assertFalse((bool) $larga->permite_congelamiento);
    }

    public function test_programar_extiende_vencimiento_de_forma_inclusiva(): void
    {
        $m = $this->membresiaVigente();
        $finOriginal = $m->fecha_fin;

        $inicio = today()->addDays(2)->format('Y-m-d');
        $fin = today()->addDays(6)->format('Y-m-d'); // 5 días inclusivos

        $this->actingAs($this->admin)->post(route('membresias.congelar', $m), [
            'fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'motivo' => 'Viaje',
        ])->assertRedirect();

        $m->refresh();
        $this->assertSame(\Carbon\Carbon::parse($finOriginal)->addDays(5)->format('Y-m-d'), $m->fecha_fin);
        $this->assertDatabaseHas('membresia_congelamientos', [
            'fkmembresia_alumno' => $m->id_membresia_alumno,
            'dias' => 5, 'estado' => 'programado',
        ]);
    }

    public function test_programar_rechaza_reglas(): void
    {
        $m = $this->membresiaVigente();

        // Inicio pasado.
        $this->actingAs($this->admin)->post(route('membresias.congelar', $m), [
            'fecha_inicio' => today()->subDay()->format('Y-m-d'),
            'fecha_fin' => today()->addDays(3)->format('Y-m-d'),
            'motivo' => 'X',
        ])->assertSessionHasErrors('error');

        // Fuera de la vigencia restante.
        $this->actingAs($this->admin)->post(route('membresias.congelar', $m), [
            'fecha_inicio' => today()->addDays(40)->format('Y-m-d'),
            'fecha_fin' => today()->addDays(45)->format('Y-m-d'),
            'motivo' => 'X',
        ])->assertSessionHasErrors('error');

        // Plan sin congelamiento.
        $sinPermiso = $this->membresiaVigente(false);
        $this->actingAs($this->admin)->post(route('membresias.congelar', $sinPermiso), [
            'fecha_inicio' => today()->format('Y-m-d'),
            'fecha_fin' => today()->addDays(2)->format('Y-m-d'),
            'motivo' => 'X',
        ])->assertSessionHasErrors('error');

        $this->assertSame(0, \App\Models\MembresiaCongelamiento::count());
    }

    public function test_solo_un_congelamiento_por_membresia(): void
    {
        $m = $this->membresiaVigente();
        $this->actingAs($this->admin)->post(route('membresias.congelar', $m), [
            'fecha_inicio' => today()->format('Y-m-d'),
            'fecha_fin' => today()->addDays(2)->format('Y-m-d'),
            'motivo' => 'Primero',
        ])->assertRedirect();

        $this->actingAs($this->admin)->post(route('membresias.congelar', $m), [
            'fecha_inicio' => today()->addDays(5)->format('Y-m-d'),
            'fecha_fin' => today()->addDays(6)->format('Y-m-d'),
            'motivo' => 'Segundo',
        ])->assertSessionHasErrors('error');
    }

    public function test_congelamiento_activo_bloquea_asistencia(): void
    {
        $m = $this->membresiaVigente();
        $alumno = $m->alumno;
        $this->actingAs($this->admin)->post(route('membresias.congelar', $m), [
            'fecha_inicio' => today()->format('Y-m-d'),
            'fecha_fin' => today()->addDays(4)->format('Y-m-d'),
            'motivo' => 'Viaje',
        ])->assertRedirect();

        $respuesta = $this->actingAs($this->local)->post(route('asistencias.store'), [
            'codigo_documento' => $alumno->alum_numDoc,
            'tipo_ingreso' => 'dni',
        ]);
        $respuesta->assertSessionHasErrors('codigo_documento');
        $this->assertDatabaseCount('visitas', 0);
    }

    public function test_cancelar_programado_revierte_todo(): void
    {
        $m = $this->membresiaVigente();
        $finOriginal = $m->fecha_fin;
        $this->actingAs($this->admin)->post(route('membresias.congelar', $m), [
            'fecha_inicio' => today()->addDays(3)->format('Y-m-d'),
            'fecha_fin' => today()->addDays(7)->format('Y-m-d'),
            'motivo' => 'Quizás viajo',
        ])->assertRedirect();

        $cong = \App\Models\MembresiaCongelamiento::firstOrFail();
        $this->actingAs($this->admin)->post(route('congelamientos.cancelar', $cong))->assertRedirect();

        $this->assertSame('cancelado', $cong->fresh()->estado);
        $this->assertSame($finOriginal, $m->fresh()->fecha_fin);

        // Cancelado no consume: se puede programar de nuevo.
        $this->actingAs($this->admin)->post(route('membresias.congelar', $m), [
            'fecha_inicio' => today()->addDays(3)->format('Y-m-d'),
            'fecha_fin' => today()->addDays(4)->format('Y-m-d'),
            'motivo' => 'Reprogramado',
        ])->assertRedirect();
    }

    public function test_finalizar_anticipado_conserva_solo_dias_usados(): void
    {
        $m = $this->membresiaVigente();
        $finOriginal = $m->fecha_fin;
        $this->actingAs($this->admin)->post(route('membresias.congelar', $m), [
            'fecha_inicio' => today()->format('Y-m-d'),
            'fecha_fin' => today()->addDays(9)->format('Y-m-d'), // 10 días
            'motivo' => 'Viaje largo',
        ])->assertRedirect();

        $cong = \App\Models\MembresiaCongelamiento::firstOrFail();
        $this->assertSame(\Carbon\Carbon::parse($finOriginal)->addDays(10)->format('Y-m-d'), $m->fresh()->fecha_fin);

        // Viaja el tiempo al día 4 del congelamiento.
        \Carbon\Carbon::setTestNow(today()->addDays(3));
        $this->actingAs($this->admin)->post(route('congelamientos.finalizar', $cong))->assertRedirect();
        \Carbon\Carbon::setTestNow();

        $cong->refresh();
        $m->refresh();
        $this->assertSame('finalizado', $cong->estado);
        $this->assertSame(4, (int) $cong->dias);
        // 10 programados - 4 usados = 6 retirados.
        $this->assertSame(\Carbon\Carbon::parse($finOriginal)->addDays(4)->format('Y-m-d'), $m->fecha_fin);
    }

    public function test_ajustar_guarda_historial_y_valida(): void
    {
        $m = $this->membresiaVigente();
        $nueva = today()->addDays(45)->format('Y-m-d');

        $this->actingAs($this->admin)->post(route('membresias.ajustar-vigencia', $m), [
            'fecha_nueva' => $nueva, 'motivo' => 'Cortesía',
        ])->assertRedirect();

        $this->assertSame($nueva, $m->fresh()->fecha_fin);
        $ajuste = \App\Models\MembresiaAjusteVigencia::where('fkmembresia_alumno', $m->id_membresia_alumno)->firstOrFail();
        $this->assertSame($nueva, $ajuste->fecha_nueva->format('Y-m-d'));
        $this->assertEquals($this->admin->id, $ajuste->fkadmin);
        $this->assertSame('Cortesía', $ajuste->motivo);

        // Fecha anterior a hoy se rechaza.
        $this->actingAs($this->admin)->post(route('membresias.ajustar-vigencia', $m), [
            'fecha_nueva' => today()->subDay()->format('Y-m-d'), 'motivo' => 'X',
        ])->assertSessionHasErrors('error');

        // Bloqueado con congelamiento activo.
        $this->actingAs($this->admin)->post(route('membresias.congelar', $m->fresh()), [
            'fecha_inicio' => today()->format('Y-m-d'),
            'fecha_fin' => today()->addDays(2)->format('Y-m-d'),
            'motivo' => 'Viaje',
        ])->assertRedirect();
        $this->actingAs($this->admin)->post(route('membresias.ajustar-vigencia', $m->fresh()), [
            'fecha_nueva' => today()->addDays(50)->format('Y-m-d'), 'motivo' => 'X',
        ])->assertSessionHasErrors('error');
    }

    public function test_congelar_o_ajustar_no_altera_venta_ni_comision(): void
    {
        $m = $this->membresiaVigente();
        $venta = Venta::factory()->create([
            'fksede' => $this->sede->id_sede, 'tipo_venta' => 'membresia',
            'venta_total' => 150, 'monto_pagado' => 150, 'saldo' => 0,
        ]);
        $comision = \App\Models\Comision::create([
            'fkuser' => $this->admin->id, 'fkventa' => $venta->id_venta,
            'comision_base' => 15, 'penalizacion' => 0, 'comision_final' => 15,
            'tipo' => 'membresia', 'estado' => 'aprobada',
        ]);

        $this->actingAs($this->admin)->post(route('membresias.congelar', $m), [
            'fecha_inicio' => today()->format('Y-m-d'),
            'fecha_fin' => today()->addDays(2)->format('Y-m-d'),
            'motivo' => 'Viaje',
        ])->assertRedirect();

        $this->assertSame(150.0, (float) $venta->fresh()->venta_total);
        $this->assertSame(0.0, (float) $venta->fresh()->saldo);
        $this->assertSame(15.0, (float) $comision->fresh()->comision_final);
    }

    public function test_local_no_puede_modificar_vigencia_pero_si_consultar(): void
    {
        $m = $this->membresiaVigente();

        $this->actingAs($this->local)->post(route('membresias.congelar', $m), [
            'fecha_inicio' => today()->format('Y-m-d'),
            'fecha_fin' => today()->addDays(2)->format('Y-m-d'),
            'motivo' => 'X',
        ])->assertForbidden();
        $this->actingAs($this->local)->post(route('membresias.ajustar-vigencia', $m), [
            'fecha_nueva' => today()->addDays(40)->format('Y-m-d'), 'motivo' => 'X',
        ])->assertForbidden();

        $this->actingAs($this->local)->get(route('alumnos.show', $m->fkalumno))->assertOk();
    }
}
