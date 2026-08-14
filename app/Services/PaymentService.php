<?php

namespace App\Services;

use App\Models\Cuota;
use App\Models\Pago;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function registrarPago(int $ventaId, float $monto, int $metodoPagoId, ?string $fechaAcordada = null): Venta
    {
        return DB::transaction(function () use ($ventaId, $monto, $metodoPagoId, $fechaAcordada) {
            $venta = Venta::findOrFail($ventaId);

            $montoPagado = $venta->monto_pagado + $monto;
            $saldo = $venta->venta_total - $montoPagado;

            $estadoPago = 'pagado';
            if ($saldo > 0) {
                $estadoPago = 'parcial';
            }

            $venta->update([
                'monto_pagado' => $montoPagado,
                'saldo' => max(0, $saldo),
                'estado_pago' => $estadoPago,
                'fkmetodo' => $metodoPagoId,
                'fecha_acordada' => $fechaAcordada,
            ]);

            if ($saldo > 0 && $fechaAcordada) {
                $this->crearCuotaParaVenta($venta, $saldo, $fechaAcordada);
            }

            return $venta->fresh();
        });
    }

    public function calcularSaldo(int $ventaId): float
    {
        $venta = Venta::findOrFail($ventaId);
        return max(0, $venta->venta_total - $venta->monto_pagado);
    }

    public function aplicarPagoACuota(int $cuotaId, float $monto): Cuota
    {
        return DB::transaction(function () use ($cuotaId, $monto) {
            $cuota = Cuota::findOrFail($cuotaId);

            $montoPagado = $cuota->monto_pagado + $monto;
            $saldo = $cuota->monto - $montoPagado;

            $estado = 'pendiente';
            if ($saldo <= 0) {
                $estado = 'pagada';
                $montoPagado = $cuota->monto;
                $saldo = 0;
            } elseif ($montoPagado > 0) {
                $estado = 'parcial';
            }

            $cuota->update([
                'monto_pagado' => $montoPagado,
                'saldo' => max(0, $saldo),
                'estado' => $estado,
                'fecha_pago_real' => $estado === 'pagada' ? now()->format('Y-m-d') : $cuota->fecha_pago_real,
            ]);

            if ($cuota->fkventa) {
                $this->actualizarVentaDesdeCuota($cuota->fkventa);
            }

            if ($cuota->fkpago) {
                $this->actualizarPagoDesdeCuota($cuota->fkpago);
            }

            return $cuota->fresh();
        });
    }

    public function crearCuotasParaVenta(int $ventaId, array $cuotasData): Venta
    {
        return DB::transaction(function () use ($ventaId, $cuotasData) {
            $venta = Venta::findOrFail($ventaId);

            foreach ($cuotasData as $cuotaData) {
                Cuota::create([
                    'fkventa' => $ventaId,
                    'numero_cuota' => $cuotaData['numero_cuota'],
                    'monto' => $cuotaData['monto'],
                    'monto_pagado' => 0,
                    'saldo' => $cuotaData['monto'],
                    'fecha_acordada' => $cuotaData['fecha_acordada'],
                    'estado' => 'pendiente',
                ]);
            }

            $totalCuotas = array_sum(array_column($cuotasData, 'monto'));
            $venta->update([
                'estado_pago' => 'parcial',
                'monto_pagado' => $venta->venta_total - $totalCuotas,
                'saldo' => $totalCuotas,
            ]);

            return $venta->fresh();
        });
    }

    public function marcarComoVencido(): int
    {
        $hoy = now()->format('Y-m-d');

        return Cuota::where('estado', 'pendiente')
            ->where('fecha_acordada', '<', $hoy)
            ->update(['estado' => 'vencida']);
    }

    protected function crearCuotaParaVenta(Venta $venta, float $saldo, string $fechaAcordada): void
    {
        $numeroCuota = Cuota::where('fkventa', $venta->id_venta)->count() + 1;

        Cuota::create([
            'fkventa' => $venta->id_venta,
            'numero_cuota' => $numeroCuota,
            'monto' => $saldo,
            'monto_pagado' => 0,
            'saldo' => $saldo,
            'fecha_acordada' => $fechaAcordada,
            'estado' => 'pendiente',
        ]);
    }

    protected function actualizarVentaDesdeCuota(int $ventaId): void
    {
        $venta = Venta::findOrFail($ventaId);
        $cuotas = Cuota::where('fkventa', $ventaId)->get();

        $totalPagadoCuotas = $cuotas->sum('monto_pagado');
        $montoPagadoTotal = $venta->venta_total - $cuotas->sum('saldo');

        $estadoPago = 'pagado';
        if ($montoPagadoTotal < $venta->venta_total) {
            $estadoPago = 'parcial';
        }

        $venta->update([
            'monto_pagado' => $montoPagadoTotal,
            'saldo' => max(0, $venta->venta_total - $montoPagadoTotal),
            'estado_pago' => $estadoPago,
        ]);
    }

    protected function actualizarPagoDesdeCuota(int $pagoId): void
    {
        $pago = Pago::findOrFail($pagoId);
        $cuotas = Cuota::where('fkpago', $pagoId)->get();

        $totalPagadoCuotas = $cuotas->sum('monto_pagado');
        $montoPagadoTotal = $pago->total - $cuotas->sum('saldo');

        $estadoPago = 'completo';
        if ($montoPagadoTotal < $pago->total) {
            $estadoPago = 'incompleto';
        }

        $pago->update([
            'monto_pagado' => $montoPagadoTotal,
            'saldo' => max(0, $pago->total - $montoPagadoTotal),
            'estado_pago' => $estadoPago,
        ]);
    }
}
