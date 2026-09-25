<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderReuseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_caja_seguimiento_y_reportes_usan_el_encabezado_global_sin_h1_local(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $paginas = [
            'caja.index' => 'Caja por sede',
            'seguimiento.index' => 'Seguimiento',
            'reportes.index' => 'Reportes',
            'reportes.ventas' => 'Reporte de Ventas',
            'reportes.membresias' => 'Reporte de Membresías',
            'reportes.productos' => 'Reporte de Productos',
            'reportes.comisiones' => 'Reporte de Comisiones',
            'reportes.gastos' => 'Reporte de Gastos',
            'reportes.caja' => 'Reporte de Caja',
            'reportes.vencimientos' => 'Reporte de Vencimientos',
        ];

        foreach ($paginas as $ruta => $titulo) {
            $response = $this->actingAs($admin)->get(route($ruta));

            $response->assertOk()->assertSeeText($titulo);
            $this->assertStringContainsString(
                '<h2 class="text-xl lg:text-2xl font-bold text-gray-900">'.$titulo.'</h2>',
                $response->getContent(),
                "La ruta {$ruta} no usa el encabezado global."
            );
            $this->assertStringNotContainsString(
                '<h1 class="text-2xl font-bold text-gray-900">'.$titulo.'</h1>',
                $response->getContent(),
                "La ruta {$ruta} todavía contiene un título local."
            );
            $this->assertStringNotContainsString('>Título<', $response->getContent(), "La ruta {$ruta} no definió el encabezado global.");
        }
    }

    public function test_dashboard_por_rol_y_ficha_de_alumno_usan_el_encabezado_global(): void
    {
        $sede = Sede::factory()->create();
        $casos = [
            'Local' => 'Dashboard Local',
            'Redes' => 'Dashboard Redes',
            'Asistencia' => 'Dashboard Asistencia',
        ];

        foreach ($casos as $rol => $titulo) {
            $usuario = User::factory()->create(['fksede' => $sede->id_sede]);
            $usuario->assignRole($rol);

            $response = $this->actingAs($usuario)->get(route('dashboard.index'));

            $response->assertOk();
            $this->assertStringContainsString(
                '<h2 class="text-xl lg:text-2xl font-bold text-gray-900">'.$titulo.'</h2>',
                $response->getContent()
            );
            $this->assertStringNotContainsString(
                '<h1 class="text-2xl font-bold text-gray-900">'.$titulo.'</h1>',
                $response->getContent()
            );
        }

        $local = User::factory()->create(['fksede' => $sede->id_sede]);
        $local->assignRole('Local');
        $alumno = Alumno::factory()->create(['fksede' => $sede->id_sede]);

        $response = $this->actingAs($local)->get(route('alumnos.show', $alumno->id_alumno));

        $response->assertOk();
        $this->assertStringContainsString(
            '<h2 class="text-xl lg:text-2xl font-bold text-gray-900">Ficha del alumno</h2>',
            $response->getContent()
        );
        $this->assertStringNotContainsString('>Título<', $response->getContent());
    }

    public function test_header_no_repite_el_usuario_y_conserva_cambio_de_contrasena(): void
    {
        $admin = User::factory()->create(['name' => 'Usuario Único del Header']);
        $admin->assignRole('Administrador');

        $response = $this->actingAs($admin)->get(route('dashboard.index'));

        $response->assertOk();
        preg_match('/<header\b.*?<\/header>/s', $response->getContent(), $coincidencia);
        $header = $coincidencia[0] ?? '';

        $this->assertNotSame('', $header);
        $this->assertStringNotContainsString($admin->name, $header);
        $this->assertStringContainsString(route('password.change.form'), $header);
        $this->assertStringContainsString('aria-label="Cambiar contraseña"', $header);
    }

    public function test_pestanas_usan_formato_comun_y_logo_del_sistema(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $interna = $this->actingAs($admin)->get(route('alumnos.index'));
        $interna->assertOk()
            ->assertSee('<title>Alumnos | SIGG</title>', false)
            ->assertSee('rel="icon" type="image/png" href="'.asset('icon/icongym.png').'"', false);

        auth()->logout();

        $login = $this->get(route('login'));
        $login->assertOk()
            ->assertSee('<title>Iniciar sesión | SIGG</title>', false)
            ->assertSee('rel="icon" type="image/png" href="'.asset('icon/icongym.png').'"', false);

        $asistencia = $this->get(route('asistencia.publica'));
        $asistencia->assertOk()
            ->assertSee('<title>Registro de asistencia | SIGG</title>', false)
            ->assertSee('rel="icon" type="image/png" href="'.asset('icon/icongym.png').'"', false);
    }
}
