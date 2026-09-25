<?php

namespace Tests\Feature\Alumnos;

use App\Models\Alumno;
use App\Models\Membresia;
use App\Models\MembresiaAlumno;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteMensualTest extends TestCase
{
    use RefreshDatabase;

    private Sede $sede;

    private User $admin;

    private User $local;

    private User $redes;

    private Membresia $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->sede = Sede::factory()->create();
        $this->admin = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->admin->assignRole('Administrador');
        $this->local = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->local->assignRole('Local');
        $this->redes = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->redes->assignRole('Redes');
        $this->plan = Membresia::factory()->create(['mem_nombre' => 'Mensual Test', 'mem_duracion' => 30]);
    }

    private function membresia(User $registrador, string $inicio, string $fin, string $estado = 'activa'): MembresiaAlumno
    {
        $alumno = Alumno::factory()->create([
            'fksede' => $this->sede->id_sede,
            'fkuser' => $registrador->id,
        ]);

        return MembresiaAlumno::create([
            'fkalumno' => $alumno->id_alumno,
            'fkmem' => $this->plan->id_mem,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
            'precio_vendido' => $this->plan->mem_precio,
            'estado' => $estado,
        ]);
    }

    private function params(array $cambios = []): array
    {
        return array_merge([
            'mes' => 5,
            'anio' => 2026,
            'alcance' => 'todos',
            'columnas' => ['codigo', 'nombres', 'plan', 'vencimiento'],
        ], $cambios);
    }

    public function test_local_y_redes_pueden_abrir_el_reporte_y_otros_roles_no(): void
    {
        $this->actingAs($this->local)->get(route('alumnos.reporte', $this->params()))->assertOk();
        $this->actingAs($this->redes)->get(route('alumnos.reporte', $this->params()))->assertOk();
        $this->actingAs($this->admin)->get(route('alumnos.reporte', $this->params()))->assertOk();

        $asistencia = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $asistencia->assignRole('Asistencia');
        $this->actingAs($asistencia)->get(route('alumnos.reporte', $this->params()))->assertForbidden();
        $this->actingAs($asistencia)->get(route('alumnos.reporte.exportar', $this->params()))->assertForbidden();
    }

    public function test_todos_incluye_membresias_que_cruzan_los_limites(): void
    {
        $this->membresia($this->local, '2026-04-20', '2026-05-03'); // cruza inicio
        $this->membresia($this->local, '2026-05-28', '2026-06-10'); // cruza fin
        $this->membresia($this->local, '2026-05-01', '2026-05-31'); // contenida
        $this->membresia($this->local, '2026-06-01', '2026-06-30'); // fuera

        $html = $this->actingAs($this->local)->get(route('alumnos.reporte', $this->params()))->assertOk()->getContent();

        $this->assertStringContainsString('Resultados:</span> 3', $html);
    }

    public function test_vencen_filtra_por_mes_para_pasado_actual_y_futuro(): void
    {
        $this->membresia($this->local, '2026-03-01', '2026-04-15'); // venció en abril
        $this->membresia($this->local, '2026-04-20', '2026-05-10'); // vence en mayo
        $this->membresia($this->local, '2026-05-20', '2026-07-01'); // vence en julio

        $params = $this->params(['alcance' => 'vencen']);
        $html = $this->actingAs($this->local)->get(route('alumnos.reporte', $params))->assertOk()->getContent();
        $this->assertStringContainsString('Resultados:</span> 1', $html);

        $html = $this->actingAs($this->local)->get(route('alumnos.reporte', $this->params(['alcance' => 'vencen', 'mes' => 4])))->assertOk()->getContent();
        $this->assertStringContainsString('Resultados:</span> 1', $html);

        $html = $this->actingAs($this->local)->get(route('alumnos.reporte', $this->params(['alcance' => 'vencen', 'mes' => 7])))->assertOk()->getContent();
        $this->assertStringContainsString('Resultados:</span> 1', $html);
    }

    public function test_renovaciones_multiples_producen_una_fila_por_membresia(): void
    {
        $alumno = Alumno::factory()->create(['fksede' => $this->sede->id_sede, 'fkuser' => $this->local->id]);
        MembresiaAlumno::create([
            'fkalumno' => $alumno->id_alumno, 'fkmem' => $this->plan->id_mem,
            'fecha_inicio' => '2026-05-01', 'fecha_fin' => '2026-05-15',
            'precio_vendido' => $this->plan->mem_precio, 'estado' => 'activa',
        ]);
        MembresiaAlumno::create([
            'fkalumno' => $alumno->id_alumno, 'fkmem' => $this->plan->id_mem,
            'fecha_inicio' => '2026-05-16', 'fecha_fin' => '2026-06-15',
            'precio_vendido' => $this->plan->mem_precio, 'estado' => 'activa',
        ]);

        $html = $this->actingAs($this->local)->get(route('alumnos.reporte', $this->params()))->assertOk()->getContent();
        $this->assertStringContainsString('Resultados:</span> 2', $html);
    }

    public function test_local_y_redes_solo_ven_sus_propios_aunque_manipulen_parametros(): void
    {
        $this->membresia($this->local, '2026-05-01', '2026-05-31');
        $this->membresia($this->admin, '2026-05-01', '2026-05-31');

        $params = $this->params(['registrador' => $this->admin->id]);
        $html = $this->actingAs($this->local)->get(route('alumnos.reporte', $params))->assertOk()->getContent();
        $this->assertStringContainsString('Resultados:</span> 1', $html);

        $html = $this->actingAs($this->redes)->get(route('alumnos.reporte', $this->params()))->assertOk()->getContent();
        $this->assertStringContainsString('Resultados:</span> 0', $html);
    }

    public function test_admin_puede_filtrar_por_sede_y_registrador(): void
    {
        $otraSede = Sede::factory()->create();
        $otroLocal = User::factory()->create(['fksede' => $otraSede->id_sede]);
        $otroLocal->assignRole('Local');
        $alumno = Alumno::factory()->create(['fksede' => $otraSede->id_sede, 'fkuser' => $otroLocal->id]);
        MembresiaAlumno::create([
            'fkalumno' => $alumno->id_alumno, 'fkmem' => $this->plan->id_mem,
            'fecha_inicio' => '2026-05-01', 'fecha_fin' => '2026-05-31',
            'precio_vendido' => $this->plan->mem_precio, 'estado' => 'activa',
        ]);
        $this->membresia($this->local, '2026-05-01', '2026-05-31');

        $html = $this->actingAs($this->admin)->get(route('alumnos.reporte', $this->params()))->assertOk()->getContent();
        $this->assertStringContainsString('Resultados:</span> 2', $html);

        $html = $this->actingAs($this->admin)->get(route('alumnos.reporte', $this->params(['sede' => $this->sede->id_sede])))->assertOk()->getContent();
        $this->assertStringContainsString('Resultados:</span> 1', $html);

        $html = $this->actingAs($this->admin)->get(route('alumnos.reporte', $this->params(['registrador' => $otroLocal->id])))->assertOk()->getContent();
        $this->assertStringContainsString('Resultados:</span> 1', $html);
    }

    public function test_columnas_vacias_desconocidas_y_sin_datos_financieros_son_rechazadas(): void
    {
        $this->actingAs($this->local)->get(route('alumnos.reporte', $this->params(['columnas' => []])))
            ->assertSessionHasErrors('columnas');

        $this->actingAs($this->local)->get(route('alumnos.reporte', $this->params(['columnas' => ['codigo', 'pag_monto']])))
            ->assertSessionHasErrors('columnas.1');
    }

    public function test_tabla_y_excel_contienen_exactamente_las_columnas_pedidas(): void
    {
        $this->membresia($this->local, '2026-05-01', '2026-05-31');

        $html = $this->actingAs($this->local)->get(route('alumnos.reporte', $this->params()))->assertOk()->getContent();
        foreach (['Código', 'Nombres', 'Plan', 'Vencimiento'] as $encabezado) {
            $this->assertStringContainsString($encabezado, $html);
        }
        $this->assertStringNotContainsString('Teléfono', $html);

        $respuesta = $this->actingAs($this->local)->get(route('alumnos.reporte.exportar', $this->params()));
        $respuesta->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $respuesta->headers->get('Content-Type')
        );
        $this->assertStringContainsString('reporte_mensual_alumnos_2026-05.xlsx', $respuesta->headers->get('Content-Disposition'));

        // El export usa exactamente los mismos filtros, orden y columnas que la tabla.
        $servicio = app(\App\Services\ReporteMensualService::class);
        $filtros = $servicio->validar($this->params());
        $filas = $servicio->construirQuery($this->local, $filtros)->get()
            ->map(fn ($m) => array_values($servicio->mapearFila($m, $filtros['columnas'])))
            ->all();
        $export = new \App\Exports\ReporteMensualExport($filas, $servicio->encabezados($filtros['columnas']));

        $this->assertSame(['Código', 'Nombres', 'Plan', 'Vencimiento'], $export->headings());
        $this->assertCount(1, $export->collection());
        $this->assertCount(4, $export->collection()->first());
    }
}
