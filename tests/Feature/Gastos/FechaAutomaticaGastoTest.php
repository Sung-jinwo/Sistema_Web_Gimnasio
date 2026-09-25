<?php

namespace Tests\Feature\Gastos;

use App\Models\Gasto;
use App\Models\MetodoPago;
use App\Models\Sede;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AbreCaja;
use Tests\TestCase;

class FechaAutomaticaGastoTest extends TestCase
{
    use AbreCaja;
    use RefreshDatabase;

    public function test_fecha_se_asigna_en_lima_y_no_acepta_manipulacion(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-24 23:30:00', 'America/Lima'));
        $sede = Sede::factory()->create();
        $local = User::factory()->create(['fksede' => $sede->id_sede]);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $local->assignRole('Local');
        $this->abrirCajaPara($local);
        $metodo = MetodoPago::factory()->create(['metod_nombre' => 'Efectivo', 'es_efectivo' => true]);

        $this->actingAs($local)->post(route('gastos.store'), [
            'gas_concepto' => 'Compra de limpieza',
            'gas_monto' => 25.50,
            'fkmetodo' => $metodo->id_metod,
            'gas_fecha' => '2000-01-01',
        ])->assertRedirect(route('gastos.index'));

        $gasto = Gasto::firstOrFail();
        $this->assertSame('2026-09-24', $gasto->gas_fecha);

        $this->put(route('gastos.update', $gasto), [
            'gas_concepto' => 'Compra de limpieza actualizada',
            'gas_monto' => 30,
            'fkmetodo' => $metodo->id_metod,
            'gas_fecha' => '2040-12-31',
        ])->assertRedirect(route('gastos.index'));

        $gasto->refresh();
        $this->assertSame('2026-09-24', $gasto->gas_fecha);
        $this->assertSame('Compra de limpieza actualizada', $gasto->gas_concepto);

        $this->get(route('gastos.index'))
            ->assertOk()
            ->assertDontSee('name="gas_fecha"', false);
    }
}
