<?php

namespace Tests\Feature\Asistencia;

use App\Models\Alumno;
use App\Models\Membresia;
use App\Models\MembresiaAlumno;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VigenciaMembresiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_uses_membership_assignment_instead_of_legacy_payment(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $sede = Sede::factory()->create();
        $local = User::factory()->create(['fksede' => $sede->id_sede]);
        $local->assignRole('Local');
        $alumno = Alumno::factory()->create(['fksede' => $sede->id_sede]);
        $membresia = Membresia::factory()->create();

        MembresiaAlumno::create([
            'fkalumno' => $alumno->id_alumno,
            'fkmem' => $membresia->id_mem,
            'fecha_inicio' => today(),
            'fecha_fin' => today()->addMonth(),
            'precio_vendido' => $membresia->mem_precio,
            'comision_aplicada' => $membresia->comision,
            'modalidad' => $membresia->modalidad,
            'estado' => 'activa',
        ]);

        $this->actingAs($local)->post(route('asistencias.store'), [
            'codigo_documento' => $alumno->alum_numDoc,
            'tipo_ingreso' => 'dni',
        ])->assertRedirect(route('asistencias.index'));

        $this->assertDatabaseHas('visitas', ['fkalum' => $alumno->id_alumno, 'fksede' => $sede->id_sede]);
        $this->assertDatabaseCount('pagos', 0);
    }
}
