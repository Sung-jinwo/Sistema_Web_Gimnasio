<?php

namespace Tests\Unit\Services;

use App\Models\Abono;
use App\Models\Caja;
use App\Models\MetodoPago;
use App\Models\Sede;
use App\Models\User;
use App\Models\Venta;
use App\Services\CashClosingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashClosingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_expected_cash_uses_payments_without_adding_sale_total_twice(): void
    {
        $sede = Sede::factory()->create();
        $user = User::factory()->create(['fksede' => $sede->id_sede]);
        $metodo = MetodoPago::factory()->create();
        $caja = Caja::create([
            'fksede' => $sede->id_sede,
            'fkuser' => $user->id,
            'fecha_apertura' => now()->subHour(),
            'monto_inicial' => 50,
            'estado' => 'abierta',
        ]);
        $venta = Venta::factory()->create([
            'fkusers' => $user->id,
            'fksede' => $sede->id_sede,
            'fkmetodo' => $metodo->id_metod,
            'estado_venta' => 'completado',
            'venta_total' => 100,
            'monto_pagado' => 100,
            'saldo' => 0,
            'estado_pago' => 'pagado',
        ]);
        Abono::create([
            'fkventa' => $venta->id_venta,
            'fkmetodo' => $metodo->id_metod,
            'fksede' => $sede->id_sede,
            'fkuser' => $user->id,
            'monto' => 100,
            'fecha_abono' => now(),
        ]);

        $montoEsperado = app(CashClosingService::class)->calcularMontoEsperado($caja);

        $this->assertSame(150.0, $montoEsperado);
    }
}
