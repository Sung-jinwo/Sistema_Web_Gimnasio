<?php

namespace Tests\Feature\Asistencia;

use App\Models\Alumno;
use App\Models\Membresia;
use App\Models\MembresiaAlumno;
use App\Models\Sede;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RegistroAsistenciaTest extends TestCase
{
    use RefreshDatabase;

    private Sede $sede;

    private User $local;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->sede = Sede::factory()->create();
        $this->local = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->local->assignRole('Local');
    }

    public function test_registro_interno_resuelve_dni_y_codigo_y_permite_varios_ingresos(): void
    {
        $alumno = $this->alumnoConMembresia();

        $this->actingAs($this->local)->post(route('asistencias.store'), [
            'codigo_documento' => $alumno->alum_numDoc,
            'tipo_ingreso' => 'dni',
        ])->assertRedirect(route('asistencias.index'));

        foreach (range(1, 2) as $intento) {
            $this->post(route('asistencias.store'), [
                'codigo_documento' => $alumno->alum_codigo,
                'tipo_ingreso' => 'codigo',
            ])->assertRedirect(route('asistencias.index'));
        }

        $this->assertDatabaseCount('visitas', 3);
        $this->assertDatabaseHas('visitas', [
            'fkalum' => $alumno->id_alumno,
            'fkuser' => $this->local->id,
            'fksede' => $this->sede->id_sede,
            'tipo_ingreso' => 'dni',
        ]);
    }

    public function test_registro_publico_admite_usuario_nulo_y_varios_ingresos(): void
    {
        $alumno = $this->alumnoConMembresia();
        $payload = ['codigo_documento' => $alumno->alum_numDoc, 'sede_id' => $this->sede->id_sede];

        $this->post(route('asistencia.publica.store'), $payload)->assertOk();
        $this->post(route('asistencia.publica.store'), $payload)->assertOk();

        $this->assertDatabaseCount('visitas', 2);
        $this->assertDatabaseHas('visitas', [
            'fkalum' => $alumno->id_alumno,
            'fkuser' => null,
            'fksede' => $this->sede->id_sede,
            'tipo_ingreso' => 'dni',
        ]);
    }

    public function test_rechaza_alumno_inexistente_inactivo_y_sin_membresia_vigente(): void
    {
        $this->actingAs($this->local)->post(route('asistencias.store'), [
            'codigo_documento' => '00000000',
            'tipo_ingreso' => 'dni',
        ])->assertSessionHasErrors('codigo_documento');

        $inactivo = Alumno::factory()->create(['fksede' => $this->sede->id_sede, 'alum_estado' => false]);
        $this->post(route('asistencias.store'), [
            'codigo_documento' => $inactivo->alum_numDoc,
            'tipo_ingreso' => 'dni',
        ])->assertSessionHasErrors('codigo_documento');

        $futuro = Alumno::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->asignarMembresia($futuro, today()->addDay(), today()->addMonth());
        $this->post(route('asistencias.store'), [
            'codigo_documento' => $futuro->alum_numDoc,
            'tipo_ingreso' => 'dni',
        ])->assertSessionHasErrors('codigo_documento');

        $vencido = Alumno::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->asignarMembresia($vencido, today()->subMonth(), today()->subDay());
        $this->post(route('asistencias.store'), [
            'codigo_documento' => $vencido->alum_numDoc,
            'tipo_ingreso' => 'dni',
        ])->assertSessionHasErrors('codigo_documento');

        $this->assertDatabaseCount('visitas', 0);
    }

    public function test_fecha_y_hora_usan_la_zona_de_lima(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-24 23:30:00', 'America/Lima'));
        $alumno = $this->alumnoConMembresia();

        $this->actingAs($this->local)->post(route('asistencias.store'), [
            'codigo_documento' => $alumno->alum_numDoc,
            'tipo_ingreso' => 'dni',
        ])->assertRedirect(route('asistencias.index'));

        $this->assertSame('America/Lima', config('app.timezone'));
        $this->assertDatabaseHas('visitas', ['visi_fecha' => '2026-09-24 23:30:00']);
    }

    public function test_permiso_de_creacion_y_rutas_inmutables(): void
    {
        $usuario = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $usuario->givePermissionTo('asistencias.ver');

        $this->actingAs($usuario)->get(route('asistencias.index'))
            ->assertOk()
            ->assertDontSee('Registrar Asistencia');

        $this->post(route('asistencias.store'), [
            'codigo_documento' => '12345678',
            'tipo_ingreso' => 'dni',
        ])->assertForbidden();

        $this->assertFalse(Route::has('asistencias.create'));
        $this->assertFalse(Route::has('asistencias.show'));
        $this->assertFalse(Route::has('asistencias.edit'));
        $this->assertFalse(Route::has('asistencias.update'));
        $this->assertFalse(Route::has('asistencias.destroy'));
    }

    public function test_listado_filtra_por_codigo_dni_y_tipo_de_ingreso(): void
    {
        $alumno = $this->alumnoConMembresia();
        $otro = $this->alumnoConMembresia();
        $this->actingAs($this->local);

        $this->post(route('asistencias.store'), ['codigo_documento' => $alumno->alum_numDoc, 'tipo_ingreso' => 'dni']);
        $this->post(route('asistencias.store'), ['codigo_documento' => $otro->alum_codigo, 'tipo_ingreso' => 'codigo']);

        $this->getJson(route('asistencias.index', ['search' => $alumno->alum_numDoc, 'tipo_ingreso' => 'dni']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.fkalum', $alumno->id_alumno);

        $this->getJson(route('asistencias.index', ['search' => $otro->alum_codigo, 'tipo_ingreso' => 'codigo']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.fkalum', $otro->id_alumno);
    }

    private function alumnoConMembresia(): Alumno
    {
        $alumno = Alumno::factory()->create(['fksede' => $this->sede->id_sede, 'alum_estado' => true]);
        $this->asignarMembresia($alumno, today(), today()->addMonth());

        return $alumno;
    }

    private function asignarMembresia(Alumno $alumno, Carbon $inicio, Carbon $fin): void
    {
        $membresia = Membresia::factory()->create();
        MembresiaAlumno::create([
            'fkalumno' => $alumno->id_alumno,
            'fkmem' => $membresia->id_mem,
            'fecha_inicio' => $inicio->toDateString(),
            'fecha_fin' => $fin->toDateString(),
            'precio_vendido' => $membresia->mem_precio,
            'comision_aplicada' => $membresia->comision,
            'modalidad' => $membresia->modalidad,
            'estado' => 'activa',
        ]);
    }
}
