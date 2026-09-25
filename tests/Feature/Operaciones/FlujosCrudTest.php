<?php

namespace Tests\Feature\Operaciones;

use App\Models\Gasto;
use App\Models\Membresia;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FlujosCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Sede $sede;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->sede = Sede::factory()->create();
        $this->admin = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $this->admin->assignRole('Administrador');
    }

    public function test_administrador_crea_y_actualiza_usuarios_y_sedes(): void
    {
        $this->actingAs($this->admin)->post(route('usuarios.store'), [
            'name' => 'Empleado Operativo',
            'email' => 'operativo@example.com',
            'password' => 'secreto123',
            'role' => 'Local',
            'fksede' => $this->sede->id_sede,
        ])->assertRedirect(route('usuarios.index'));

        $usuario = User::where('email', 'operativo@example.com')->firstOrFail();
        $this->put(route('usuarios.update', $usuario), [
            'name' => 'Empleado Actualizado',
            'email' => $usuario->email,
            'password' => '',
            'role' => 'Local',
            'fksede' => $this->sede->id_sede,
        ])->assertRedirect(route('usuarios.index'));
        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'name' => 'Empleado Actualizado']);

        $this->post(route('sedes.store'), [
            'sede_nombre' => 'Sede Operativa',
            'sede_direccion' => 'Av. Principal 123',
            'sede_estado' => 1,
        ])->assertRedirect(route('sedes.index'));

        $sede = Sede::where('sede_nombre', 'Sede Operativa')->firstOrFail();
        $this->put(route('sedes.update', $sede), [
            'sede_nombre' => 'Sede Actualizada',
            'sede_direccion' => 'Av. Principal 456',
            'sede_estado' => 1,
        ])->assertRedirect(route('sedes.index'));
        $this->assertDatabaseHas('sedes', ['id_sede' => $sede->id_sede, 'sede_nombre' => 'Sede Actualizada']);
    }

    public function test_endpoints_de_edicion_para_modales_no_renderizan_vistas_inexistentes(): void
    {
        $membresia = Membresia::factory()->create();
        $usuario = User::factory()->create(['fksede' => $this->sede->id_sede]);
        $usuario->assignRole('Local');
        $gasto = Gasto::create([
            'fksede' => $this->sede->id_sede,
            'fkuser' => $this->admin->id,
            'gas_fecha' => today(),
            'gas_concepto' => 'Gasto pendiente',
            'gas_monto' => 10,
            'estado' => 'pendiente',
        ]);

        $this->actingAs($this->admin)->getJson(route('membresias.edit', $membresia))->assertOk();
        $this->getJson(route('gastos.edit', $gasto))->assertOk();
        $this->getJson(route('usuarios.edit', $usuario))->assertOk();
        $this->getJson(route('sedes.edit', $this->sede))->assertOk();

        $this->get(route('membresias.edit', $membresia))->assertRedirect(route('membresias.index'));
        $this->get(route('gastos.edit', $gasto))->assertRedirect(route('gastos.index'));
        $this->get(route('usuarios.edit', $usuario))->assertRedirect(route('usuarios.index'));
        $this->get(route('sedes.edit', $this->sede))->assertRedirect(route('sedes.index'));

        $this->assertFalse(Route::has('usuarios.create'));
        $this->assertFalse(Route::has('usuarios.show'));
    }
}
