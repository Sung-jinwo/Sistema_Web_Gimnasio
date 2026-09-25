<?php

namespace Tests\Feature\Seguimiento;

use App\Models\Alumno;
use App\Models\Membresia;
use App\Models\MembresiaAlumno;
use App\Models\Sede;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeguimientoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-15 10:00:00');
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_periodo_actual_se_selecciona_y_separa_vencimientos_del_mes(): void
    {
        $sede = Sede::factory()->create();
        $empleado = User::factory()->create(['fksede' => $sede->id_sede]);
        $empleado->assignRole('Local');
        $plan = Membresia::factory()->create();

        $porVencer = Alumno::factory()->create([
            'alum_nombre' => 'Futuro',
            'alum_apellido' => 'Septiembre',
            'fksede' => $sede->id_sede,
            'fkuser' => $empleado->id,
        ]);
        $vencido = Alumno::factory()->create([
            'alum_nombre' => 'Vencido',
            'alum_apellido' => 'Septiembre',
            'fksede' => $sede->id_sede,
            'fkuser' => $empleado->id,
        ]);
        $otroMes = Alumno::factory()->create([
            'alum_nombre' => 'Futuro',
            'alum_apellido' => 'Octubre',
            'fksede' => $sede->id_sede,
            'fkuser' => $empleado->id,
        ]);

        $this->crearAsignacion($porVencer, $plan, '2026-09-28');
        $this->crearAsignacion($vencido, $plan, '2026-09-10');
        $this->crearAsignacion($otroMes, $plan, '2026-10-05');

        $response = $this->actingAs($empleado)->get(route('seguimiento.index'));

        $response->assertOk()
            ->assertViewHas('filtros', fn (array $filtros) => $filtros['mes'] === 9 && $filtros['anio'] === 2026)
            ->assertSee('Futuro Septiembre')
            ->assertDontSee('Vencido Septiembre')
            ->assertDontSee('Futuro Octubre');

        $this->actingAs($empleado)->get(route('seguimiento.vencidos'))
            ->assertOk()
            ->assertSee('Vencido Septiembre')
            ->assertDontSee('Futuro Septiembre')
            ->assertDontSee('Futuro Octubre');
    }

    public function test_empleado_solo_ve_sus_alumnos_aunque_manipule_los_filtros(): void
    {
        $sede = Sede::factory()->create();
        $otraSede = Sede::factory()->create();
        $empleado = User::factory()->create(['fksede' => $sede->id_sede]);
        $empleado->assignRole('Local');
        $otroEmpleado = User::factory()->create(['fksede' => $otraSede->id_sede]);
        $otroEmpleado->assignRole('Local');
        $plan = Membresia::factory()->create();

        $propio = Alumno::factory()->create([
            'alum_nombre' => 'Alumno',
            'alum_apellido' => 'Propio',
            'fksede' => $sede->id_sede,
            'fkuser' => $empleado->id,
        ]);
        $ajeno = Alumno::factory()->create([
            'alum_nombre' => 'Alumno',
            'alum_apellido' => 'Ajeno',
            'fksede' => $otraSede->id_sede,
            'fkuser' => $otroEmpleado->id,
        ]);
        $this->crearAsignacion($propio, $plan, '2026-09-25');
        $this->crearAsignacion($ajeno, $plan, '2026-09-25');

        $this->actingAs($empleado)->get(route('seguimiento.index', [
            'sede' => $otraSede->id_sede,
            'empleado' => $otroEmpleado->id,
        ]))->assertOk()
            ->assertSee('Alumno Propio')
            ->assertDontSee('Alumno Ajeno');

        $this->actingAs($empleado)->get(route('seguimiento.whatsapp', $ajeno))
            ->assertNotFound();
    }

    public function test_administrador_puede_filtrar_por_sede_y_empleado(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');
        $sede = Sede::factory()->create();
        $otraSede = Sede::factory()->create();
        $empleado = User::factory()->create(['name' => 'Registrador Uno', 'fksede' => $sede->id_sede]);
        $otroEmpleado = User::factory()->create(['name' => 'Registrador Dos', 'fksede' => $otraSede->id_sede]);
        $sinAlumnos = User::factory()->create(['name' => 'Usuario Sin Alumnos', 'fksede' => $sede->id_sede]);
        $plan = Membresia::factory()->create();

        $alumno = Alumno::factory()->create([
            'alum_nombre' => 'Filtrado',
            'alum_apellido' => 'Visible',
            'fksede' => $sede->id_sede,
            'fkuser' => $empleado->id,
        ]);
        $otroAlumno = Alumno::factory()->create([
            'alum_nombre' => 'Filtrado',
            'alum_apellido' => 'Oculto',
            'fksede' => $otraSede->id_sede,
            'fkuser' => $otroEmpleado->id,
        ]);
        $this->crearAsignacion($alumno, $plan, '2026-09-25');
        $this->crearAsignacion($otroAlumno, $plan, '2026-09-25');

        $this->actingAs($admin)->get(route('seguimiento.index', [
            'sede' => $sede->id_sede,
            'empleado' => $empleado->id,
        ]))->assertOk()
            ->assertSee('Filtrado Visible')
            ->assertDontSee('Filtrado Oculto')
            ->assertSee('Registrador Uno')
            ->assertSee('Registrador Dos')
            ->assertDontSee($sinAlumnos->name);
    }

    public function test_pagos_pendientes_se_normaliza_y_cobranza_respeta_permisos(): void
    {
        $sede = Sede::factory()->create();
        $local = User::factory()->create(['fksede' => $sede->id_sede]);
        $local->assignRole('Local');
        $redes = User::factory()->create(['fksede' => $sede->id_sede]);
        $redes->assignRole('Redes');

        $this->actingAs($local)->getJson(route('seguimiento.index', ['tab' => 'pagos_pendientes']))
            ->assertOk()
            ->assertJsonPath('tab', 'por_vencer');

        $this->actingAs($local)->get(route('seguimiento.index'))
            ->assertOk()
            ->assertSee('Ir a Cobranza')
            ->assertDontSee('Pagos Pendientes');

        $this->actingAs($redes)->get(route('seguimiento.index'))
            ->assertOk()
            ->assertSee('Ir a Cobranza')
            ->assertDontSee('Pagos Pendientes');
    }

    private function crearAsignacion(Alumno $alumno, Membresia $plan, string $fechaFin): MembresiaAlumno
    {
        return MembresiaAlumno::create([
            'fkalumno' => $alumno->id_alumno,
            'fkmem' => $plan->id_mem,
            'fecha_inicio' => '2026-08-01',
            'fecha_fin' => $fechaFin,
            'precio_vendido' => $plan->mem_precio,
            'comision_aplicada' => 0,
            'modalidad' => 'por_meses',
            'estado' => 'activa',
        ]);
    }
}
