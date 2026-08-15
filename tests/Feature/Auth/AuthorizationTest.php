<?php

namespace Tests\Feature\Auth;

use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_admin_can_access_all_modules(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $this->actingAs($admin);

        $this->get('/alumnos')->assertStatus(200);
        $this->get('/membresias')->assertStatus(200);
        $this->get('/productos')->assertStatus(200);
        $this->get('/ventas')->assertStatus(200);
        $this->get('/pagos')->assertStatus(200);
        $this->get('/gastos')->assertStatus(200);
        $this->get('/caja')->assertStatus(200);
        $this->get('/usuarios')->assertStatus(200);
        $this->get('/sedes')->assertStatus(200);
        $this->get('/auditoria')->assertStatus(200);
    }

    public function test_local_user_can_access_allowed_modules(): void
    {
        $sede = Sede::factory()->create();
        $local = User::factory()->create(['fksede' => $sede->id_sede]);
        $local->assignRole('Local');

        $this->actingAs($local);

        $this->get('/alumnos')->assertStatus(200);
        $this->get('/membresias')->assertStatus(200);
        $this->get('/productos')->assertStatus(200);
        $this->get('/ventas')->assertStatus(200);
        $this->get('/pagos')->assertStatus(200);
        $this->get('/gastos')->assertStatus(200);
        $this->get('/caja')->assertStatus(200);
    }

    public function test_local_user_cannot_access_admin_modules(): void
    {
        $sede = Sede::factory()->create();
        $local = User::factory()->create(['fksede' => $sede->id_sede]);
        $local->assignRole('Local');

        $this->actingAs($local);

        $this->get('/usuarios')->assertStatus(403);
        $this->get('/sedes')->assertStatus(403);
        $this->get('/auditoria')->assertStatus(403);
    }

    public function test_redes_user_can_access_student_modules(): void
    {
        $sede = Sede::factory()->create();
        $redes = User::factory()->create(['fksede' => $sede->id_sede]);
        $redes->assignRole('Redes');

        $this->actingAs($redes);

        $this->get('/alumnos')->assertStatus(200);
        $this->get('/membresias')->assertStatus(200);
        $this->get('/seguimiento')->assertStatus(200);
    }

    public function test_redes_user_cannot_access_commercial_modules(): void
    {
        $sede = Sede::factory()->create();
        $redes = User::factory()->create(['fksede' => $sede->id_sede]);
        $redes->assignRole('Redes');

        $this->actingAs($redes);

        $this->get('/productos')->assertStatus(403);
        $this->get('/ventas')->assertStatus(403);
        $this->get('/gastos')->assertStatus(403);
        $this->get('/caja')->assertStatus(403);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/alumnos')->assertRedirect('/login');
        $this->get('/ventas')->assertRedirect('/login');
        $this->get('/usuarios')->assertRedirect('/login');
    }

    public function test_public_attendance_page_is_accessible_without_auth(): void
    {
        $this->get('/asistencia')->assertStatus(200);
    }

    public function test_admin_can_load_and_update_user_from_modal(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');
        $usuario = User::factory()->create();
        $usuario->assignRole('Local');

        $this->actingAs($admin)->getJson(route('usuarios.edit', $usuario))
            ->assertOk()->assertJsonPath('data.id', $usuario->id);

        $this->put(route('usuarios.update', $usuario), [
            'name' => 'Usuario Actualizado',
            'email' => $usuario->email,
            'password' => '',
            'role' => 'Local',
            'fksede' => $usuario->fksede,
            'telefono' => '999888777',
        ])->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'name' => 'Usuario Actualizado']);
    }
}
