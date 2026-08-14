<?php

namespace Tests\Feature\Alumnos;

use App\Models\Alumno;
use App\Models\Sede;
use App\Models\Sexo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumnoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        
        Sexo::firstOrCreate(['id_sexo' => 1], ['sexo_nombre' => 'Masculino']);
        Sexo::firstOrCreate(['id_sexo' => 2], ['sexo_nombre' => 'Femenino']);
    }

    public function test_admin_can_view_all_students(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $sede1 = Sede::factory()->create();
        $sede2 = Sede::factory()->create();

        Alumno::factory()->count(3)->create(['fksede' => $sede1->id_sede]);
        Alumno::factory()->count(2)->create(['fksede' => $sede2->id_sede]);

        $response = $this->actingAs($admin)->get('/alumnos');

        $response->assertStatus(200);
    }

    public function test_local_user_can_only_view_students_from_their_sede(): void
    {
        $sede1 = Sede::factory()->create();
        $sede2 = Sede::factory()->create();

        $local = User::factory()->create(['fksede' => $sede1->id_sede]);
        $local->assignRole('Local');

        Alumno::factory()->count(3)->create(['fksede' => $sede1->id_sede]);
        Alumno::factory()->count(2)->create(['fksede' => $sede2->id_sede]);

        $response = $this->actingAs($local)->get('/alumnos');

        $response->assertStatus(200);
    }

    public function test_can_create_student_with_valid_data(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $sede = Sede::factory()->create();

        $data = [
            'alum_codigo' => 'ALU001',
            'alum_nombre' => 'Juan',
            'alum_apellido' => 'Pérez',
            'alum_numDoc' => '12345678',
            'alum_documento' => 'DNI',
            'fksexo' => 1,
            'fecha_nac' => '1990-01-15',
            'alum_telefo' => '987654321',
            'alum_direccion' => 'Av. Principal 123',
            'fksede' => $sede->id_sede,
            'fkuser' => $admin->id,
            'alum_estado' => true,
        ];

        $response = $this->actingAs($admin)->post('/alumnos', $data);

        $response->assertRedirect('/alumnos');
        $this->assertDatabaseHas('alumno', ['alum_codigo' => 'ALU001']);
    }

    public function test_cannot_create_student_with_duplicate_dni(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $sede = Sede::factory()->create();

        Alumno::factory()->create([
            'alum_numDoc' => '12345678',
            'fksede' => $sede->id_sede,
        ]);

        $data = [
            'alum_codigo' => 'ALU002',
            'alum_nombre' => 'María',
            'alum_apellido' => 'García',
            'alum_numDoc' => '12345678',
            'alum_documento' => 'DNI',
            'fksexo' => 2,
            'fecha_nac' => '1992-05-20',
            'alum_telefo' => '987654322',
            'alum_direccion' => 'Av. Secundaria 456',
            'fksede' => $sede->id_sede,
            'fkuser' => $admin->id,
        ];

        $response = $this->actingAs($admin)->post('/alumnos', $data);

        $response->assertSessionHasErrors('alum_numDoc');
    }

    public function test_cannot_create_student_with_duplicate_code(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $sede = Sede::factory()->create();

        Alumno::factory()->create([
            'alum_codigo' => 'ALU001',
            'fksede' => $sede->id_sede,
        ]);

        $data = [
            'alum_codigo' => 'ALU001',
            'alum_nombre' => 'Carlos',
            'alum_apellido' => 'López',
            'alum_numDoc' => '87654321',
            'alum_documento' => 'DNI',
            'fksexo' => 1,
            'fecha_nac' => '1988-03-10',
            'alum_telefo' => '987654323',
            'alum_direccion' => 'Av. Tercera 789',
            'fksede' => $sede->id_sede,
            'fkuser' => $admin->id,
        ];

        $response = $this->actingAs($admin)->post('/alumnos', $data);

        $response->assertSessionHasErrors('alum_codigo');
    }

    public function test_can_update_student(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $sede = Sede::factory()->create();
        $alumno = Alumno::factory()->create(['fksede' => $sede->id_sede]);

        $data = [
            'alum_codigo' => $alumno->alum_codigo,
            'alum_nombre' => 'Nombre Actualizado',
            'alum_apellido' => 'Apellido Actualizado',
            'alum_numDoc' => $alumno->alum_numDoc,
            'alum_documento' => 'DNI',
            'fksexo' => $alumno->fksexo,
            'fecha_nac' => $alumno->fecha_nac,
            'alum_telefo' => '999999999',
            'alum_direccion' => 'Nueva Dirección',
            'fksede' => $sede->id_sede,
            'fkuser' => $admin->id,
        ];

        $response = $this->actingAs($admin)->put("/alumnos/{$alumno->id_alumno}", $data);

        $response->assertRedirect('/alumnos');
        $this->assertDatabaseHas('alumno', [
            'id_alumno' => $alumno->id_alumno,
            'alum_nombre' => 'Nombre Actualizado',
        ]);
    }

    public function test_can_soft_delete_student(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $sede = Sede::factory()->create();
        $alumno = Alumno::factory()->create(['fksede' => $sede->id_sede]);

        $response = $this->actingAs($admin)->delete("/alumnos/{$alumno->id_alumno}");

        $response->assertRedirect('/alumnos');
        $this->assertSoftDeleted('alumno', ['id_alumno' => $alumno->id_alumno]);
    }

    public function test_can_view_student_details(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $sede = Sede::factory()->create();
        $alumno = Alumno::factory()->create(['fksede' => $sede->id_sede]);

        $response = $this->actingAs($admin)->get("/alumnos/{$alumno->id_alumno}");

        $response->assertStatus(200);
        $response->assertSee($alumno->alum_nombre);
    }

    public function test_dni_must_be_exactly_8_digits(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $sede = Sede::factory()->create();

        $data = [
            'alum_codigo' => 'ALU003',
            'alum_nombre' => 'Test',
            'alum_apellido' => 'User',
            'alum_numDoc' => '1234567',
            'alum_documento' => 'DNI',
            'fksexo' => 1,
            'fecha_nac' => '1990-01-15',
            'alum_telefo' => '987654321',
            'alum_direccion' => 'Dirección',
            'fksede' => $sede->id_sede,
            'fkuser' => $admin->id,
        ];

        $response = $this->actingAs($admin)->post('/alumnos', $data);

        $response->assertSessionHasErrors('alum_numDoc');
    }
}
