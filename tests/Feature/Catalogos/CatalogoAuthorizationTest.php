<?php

namespace Tests\Feature\Catalogos;

use App\Models\Alumno;
use App\Models\Categoria;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Sede;
use App\Models\User;
use App\Services\MembresiaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CatalogoAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_administrador_administra_membresias_productos_y_categorias(): void
    {
        $sede = Sede::factory()->create();
        $admin = User::factory()->create(['fksede' => $sede->id_sede]);
        $admin->assignRole('Administrador');
        $categoria = Categoria::factory()->create();

        $this->actingAs($admin)->post(route('membresias.store'), $this->datosMembresia())
            ->assertRedirect(route('membresias.index'));
        $membresia = Membresia::where('mem_nombre', 'Plan Administrado')->firstOrFail();

        $this->getJson(route('membresias.edit', $membresia))->assertOk();
        $this->put(route('membresias.update', $membresia), $this->datosMembresia(['mem_precio' => 120]))
            ->assertRedirect(route('membresias.index'));
        $this->delete(route('membresias.destroy', $membresia))->assertRedirect(route('membresias.index'));
        $this->assertDatabaseHas('membresias', ['id_mem' => $membresia->id_mem, 'mem_precio' => 120, 'estado' => 'I']);

        $this->post(route('productos.store'), $this->datosProducto($sede, $categoria))
            ->assertRedirect(route('productos.index'));
        $producto = Producto::where('prod_nombre', 'Producto Administrado')->firstOrFail();

        $this->put(route('productos.update', $producto), $this->datosProducto($sede, $categoria, ['prod_precio' => 25]))
            ->assertRedirect(route('productos.index'));
        $this->delete(route('productos.destroy', $producto))->assertRedirect(route('productos.index'));
        $this->assertDatabaseHas('productos', ['id_productos' => $producto->id_productos, 'prod_precio' => 25, 'prod_estado' => false]);

        $this->post(route('categorias.store'), ['cat_nombre' => 'Categoría Administrada'])->assertRedirect();
        $this->assertDatabaseHas('categorias', ['cat_nombre' => 'Categoría Administrada']);

        $this->get(route('membresias.index'))->assertOk()->assertSee('Nueva Membresía')->assertSee('Activar');
        $this->get(route('productos.index'))->assertOk()->assertSee('Nuevo producto')->assertSee('title="Editar"', false);
        $this->get(route('categorias.index'))->assertOk()->assertSee('Nueva categoría')->assertSee('Nombre de la categoría');
    }

    public function test_local_solo_consulta_catalogos_y_no_ve_controles_administrativos(): void
    {
        $sede = Sede::factory()->create();
        $local = User::factory()->create(['fksede' => $sede->id_sede]);
        $local->assignRole('Local');
        $membresia = Membresia::factory()->create();
        $producto = Producto::factory()->create(['fksede' => $sede->id_sede]);

        $this->assertFalse($local->can('create', Membresia::class));
        $this->assertFalse($local->can('update', $membresia));
        $this->assertFalse($local->can('create', Producto::class));
        $this->assertFalse($local->can('update', $producto));

        $this->actingAs($local)->get(route('membresias.index'))
            ->assertOk()
            ->assertDontSee('Nueva Membresía')
            ->assertDontSee('Editar Membresía')
            ->assertDontSee('Desactivar');

        $this->get(route('productos.index'))
            ->assertOk()
            ->assertDontSee('Nuevo producto')
            ->assertDontSee('Complete los datos del inventario')
            ->assertDontSee('title="Editar"', false);

        $this->get(route('categorias.index'))
            ->assertOk()
            ->assertDontSee('Nueva categoría')
            ->assertDontSee('Nombre de la categoría');

        $this->post(route('membresias.store'), $this->datosMembresia())->assertForbidden();
        $this->put(route('membresias.update', $membresia), $this->datosMembresia())->assertForbidden();
        $this->delete(route('membresias.destroy', $membresia))->assertForbidden();
        $this->post(route('productos.store'), [])->assertForbidden();
        $this->put(route('productos.update', $producto), [])->assertForbidden();
        $this->delete(route('productos.destroy', $producto))->assertForbidden();
        $this->post(route('categorias.store'), [])->assertForbidden();
    }

    public function test_redes_y_asistencia_conservan_solo_los_accesos_definidos(): void
    {
        $sede = Sede::factory()->create();
        $redes = User::factory()->create(['fksede' => $sede->id_sede]);
        $redes->assignRole('Redes');
        $asistencia = User::factory()->create(['fksede' => $sede->id_sede]);
        $asistencia->assignRole('Asistencia');

        $this->actingAs($redes)->get(route('membresias.index'))->assertOk();
        $this->get(route('productos.index'))->assertForbidden();
        $this->post(route('membresias.store'), $this->datosMembresia())->assertForbidden();

        $this->actingAs($asistencia)->get(route('membresias.index'))->assertForbidden();
        $this->get(route('productos.index'))->assertForbidden();
    }

    public function test_asignacion_manual_desaparece_y_servicio_exige_venta(): void
    {
        $sede = Sede::factory()->create();
        $admin = User::factory()->create(['fksede' => $sede->id_sede]);
        $admin->assignRole('Administrador');
        $alumno = Alumno::factory()->create(['fksede' => $sede->id_sede]);
        $membresia = Membresia::factory()->create();

        $this->assertFalse(Route::has('membresias.asignar'));
        $this->assertFalse(Route::has('membresias.renovar'));

        $this->actingAs($admin)->get(route('alumnos.show', $alumno))
            ->assertOk()
            ->assertDontSee('Asignar Membresía');
        $this->post("/alumnos/{$alumno->id_alumno}/membresias/asignar", [])->assertNotFound();

        $this->expectException(\LogicException::class);
        app(MembresiaService::class)->asignarMembresia(
            $alumno->id_alumno,
            $membresia->id_mem,
            'por_meses',
            today()->format('Y-m-d')
        );
    }

    public function test_ventas_rechazan_catalogos_inactivos_y_productos_de_otra_sede(): void
    {
        $sede = Sede::factory()->create();
        $otraSede = Sede::factory()->create();
        $local = User::factory()->create(['fksede' => $sede->id_sede]);
        $local->assignRole('Local');
        \App\Models\Caja::create([
            'fksede' => $sede->id_sede,
            'fkuser' => $local->id,
            'fecha_apertura' => now(),
            'monto_inicial' => 0,
            'estado' => 'abierta',
        ]);
        $alumno = Alumno::factory()->create(['fksede' => $sede->id_sede]);
        $metodo = MetodoPago::factory()->create();
        $membresia = Membresia::factory()->create(['estado' => 'I']);
        $productoInactivo = Producto::factory()->create(['fksede' => $sede->id_sede, 'prod_estado' => false, 'prod_cantidad' => 5]);
        $productoOtraSede = Producto::factory()->create(['fksede' => $otraSede->id_sede, 'prod_estado' => true, 'prod_cantidad' => 5]);

        $this->actingAs($local)->postJson(route('ventas.store'), [
            'tipo_venta' => 'membresia',
            'fkalum' => $alumno->id_alumno,
            'fkmem' => $membresia->id_mem,
            'fkmetodo' => $metodo->id_metod,
            'monto_pagado' => $membresia->mem_precio,
        ])->assertUnprocessable()->assertJsonPath('message', 'La membresía seleccionada no está disponible.');

        foreach ([$productoInactivo, $productoOtraSede] as $producto) {
            $this->postJson(route('ventas.store'), [
                'tipo_venta' => 'producto',
                'fkalum' => $alumno->id_alumno,
                'fkproducto' => $producto->id_productos,
                'cantidad' => 1,
                'fkmetodo' => $metodo->id_metod,
                'monto_pagado' => $producto->prod_precio,
            ])->assertUnprocessable()->assertJsonPath('message', 'El producto no está disponible para esta sede.');
        }
    }

    private function datosMembresia(array $cambios = []): array
    {
        return array_merge([
            'mem_nombre' => 'Plan Administrado',
            'mem_precio' => 100,
            'comision' => 10,
            'modalidad' => 'por_meses',
            'mem_duracion' => 30,
            'mem_categoria' => 'Regular',
            'mem_tipo' => 'Mensual',
        ], $cambios);
    }

    private function datosProducto(Sede $sede, Categoria $categoria, array $cambios = []): array
    {
        return array_merge([
            'prod_nombre' => 'Producto Administrado',
            'prod_codigo' => 'ADMIN-001',
            'prod_precio' => 20,
            'prod_cantidad' => 10,
            'prod_stock_minimo' => 2,
            'fkcategoria' => $categoria->id_categoria,
            'fksede' => $sede->id_sede,
        ], $cambios);
    }
}
