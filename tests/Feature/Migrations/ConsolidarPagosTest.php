<?php

namespace Tests\Feature\Migrations;

use App\Models\Alumno;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsolidarPagosTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_payment_migration_is_idempotent_and_preserves_amounts(): void
    {
        $sede = Sede::factory()->create();
        $user = User::factory()->create(['fksede' => $sede->id_sede]);
        $alumno = Alumno::factory()->create(['fksede' => $sede->id_sede]);
        $metodo = MetodoPago::factory()->create();
        $membresia = Membresia::factory()->create(['mem_precio' => 150]);

        $pago = Pago::create([
            'fkalum' => $alumno->id_alumno,
            'fkuser' => $user->id,
            'fksede' => $sede->id_sede,
            'fkmetodo' => $metodo->id_metod,
            'fkmem' => $membresia->id_mem,
            'tipo_membresia' => 'principal',
            'pag_inicio' => today()->format('Y-m-d'),
            'pag_fin' => today()->addDays(30)->format('Y-m-d'),
            'fecha_limite_pago' => today()->addDays(5)->format('Y-m-d'),
            'estado_pago' => 'incompleto',
            'pag_monto' => 150,
            'total' => 150,
            'monto_pagado' => 50,
            'saldo' => 100,
        ]);

        $migrationPath = database_path('migrations/2026_09_23_000001_consolidate_pagos_into_cobranza.php');
        (require $migrationPath)->up();
        (require $migrationPath)->up();

        $this->assertDatabaseHas('ventas', [
            'legacy_pago_id' => $pago->id_pag,
            'tipo_venta' => 'membresia',
            'venta_total' => 150,
            'monto_pagado' => 50,
            'saldo' => 100,
            'estado_pago' => 'parcial',
        ]);
        $this->assertDatabaseHas('abonos', ['legacy_pago_id' => $pago->id_pag, 'monto' => 50]);
        $this->assertDatabaseCount('ventas', 1);
        $this->assertDatabaseCount('abonos', 1);
    }

    public function test_database_seeder_finishes_with_legacy_payments_consolidated(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $pagos = Pago::count();

        $this->assertGreaterThan(0, $pagos);
        $this->assertSame($pagos, \App\Models\Venta::whereNotNull('legacy_pago_id')->count());
        $this->assertSame($pagos, \App\Models\Abono::whereNotNull('legacy_pago_id')->count());
    }
}
