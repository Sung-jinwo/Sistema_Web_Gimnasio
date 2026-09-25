<?php

namespace App\Services;

use App\Models\Abono;
use App\Models\Caja;
use App\Models\Cuota;
use App\Models\Pago;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly InventoryReservationService $inventario,
        private readonly CommissionService $commissionService
    ) {}

    public function registrarCobroInicial(Venta $venta, array $cobros, array $datos = []): void
    {
        foreach ($cobros as $indice => $cobro) {
            $monto = (float) $cobro['monto'];
            if ($monto <= 0) {
                continue;
            }
            $abono = Abono::create([
                'fkventa' => $venta->id_venta,
                'fkmetodo' => $cobro['fkmetodo'],
                'fksede' => $venta->fksede,
                'fkcaja' => $venta->fkcaja,
                'fkuser' => $venta->fkusers,
                'monto' => $monto,
                'fecha_abono' => now(),
                'num_comprobante' => $datos['num_comprobante'] ?? null,
                'observacion' => count($cobros) > 1 ? 'Cobro inicial '.($indice + 1).' de la venta' : 'Cobro inicial de la venta',
            ]);

            $this->auditarAbono($abono);
        }

        if ($venta->saldo > 0 && $venta->fecha_acordada) {
            Cuota::firstOrCreate(
                ['fkventa' => $venta->id_venta, 'numero_cuota' => 1],
                [
                    'monto' => $venta->saldo,
                    'monto_pagado' => 0,
                    'saldo' => $venta->saldo,
                    'fecha_acordada' => $venta->fecha_acordada,
                    'estado' => 'pendiente',
                ]
            );
        }
    }

    public function registrarAbono(int $ventaId, array $datos): Abono
    {
        return DB::transaction(function () use ($ventaId, $datos) {
            $venta = Venta::lockForUpdate()->findOrFail($ventaId);
            $caja = Caja::lockForUpdate()->find($datos['fkcaja'] ?? null);
            if (! $caja || $caja->estado !== 'abierta' || ! $caja->fecha_operativa?->isSameDay(today())) {
                throw ValidationException::withMessages(['caja' => 'La caja ya no está disponible para registrar el abono.']);
            }
            $monto = (float) $datos['monto'];

            if ($venta->estado_venta === 'anulado') {
                throw ValidationException::withMessages(['monto' => 'No se puede abonar a una venta anulada.']);
            }

            if ($monto <= 0 || $monto > (float) $venta->saldo) {
                throw ValidationException::withMessages(['monto' => 'El abono debe ser mayor a cero y no exceder el saldo pendiente.']);
            }

            $esCobroFinal = $monto >= (float) $venta->saldo;
            if ($esCobroFinal && $venta->stock_liberado_at) {
                $this->inventario->reapartarParaCobroFinal($venta);
                $venta->refresh();
            }

            $abono = Abono::create([
                'fkventa' => $venta->id_venta,
                'fkmetodo' => $datos['fkmetodo'],
                'fksede' => $datos['fksede'] ?? $venta->fksede,
                'fkcaja' => $datos['fkcaja'] ?? $venta->fkcaja,
                'fkuser' => $datos['fkuser'] ?? auth()->id() ?? $venta->fkusers,
                'monto' => $monto,
                'fecha_abono' => $datos['fecha_abono'] ?? now(),
                'num_comprobante' => $datos['num_comprobante'] ?? null,
                'observacion' => $datos['observacion'] ?? null,
            ]);

            $cuotasAplicadas = $this->aplicarMontoACuotas($venta, $monto);
            if (count($cuotasAplicadas) === 1) {
                $abono->update(['fkcuota' => $cuotasAplicadas[0]]);
            }

            $montoPagado = min((float) $venta->venta_total, (float) $venta->monto_pagado + $monto);
            $saldo = max(0, (float) $venta->venta_total - $montoPagado);
            $estadoPago = $saldo <= 0
                ? 'pagado'
                : ($venta->fecha_acordada?->isBefore(today()) ? 'vencido' : 'parcial');
            $fechaAcordadaPrevia = $venta->fecha_acordada?->format('Y-m-d');

            // Se recalcula antes de actualizar: al completarse el pago la
            // fecha acordada se limpia y se perdería la referencia de mora.
            $this->commissionService->actualizarPenalizacionVenta($venta);

            $venta->update([
                'monto_pagado' => $montoPagado,
                'saldo' => $saldo,
                'estado_pago' => $estadoPago,
                'fecha_acordada' => $saldo <= 0 ? null : $venta->fecha_acordada,
                'pagada_at' => $saldo <= 0 ? ($datos['fecha_abono'] ?? now()) : null,
                'estado_venta' => $saldo <= 0 && $venta->tipo_venta === 'producto' ? 'completado' : $venta->estado_venta,
            ]);

            if ($saldo <= 0) {
                $this->commissionService->habilitarComisionPorCobroFinal(
                    $venta->fresh(),
                    $abono->fkcaja,
                    $fechaAcordadaPrevia
                );
            }

            $this->auditarAbono($abono->fresh());

            return $abono->fresh(['venta', 'metodo', 'user']);
        });
    }

    public function registrarPago(int $ventaId, float $monto, int $metodoPagoId, ?string $fechaAcordada = null): Venta
    {
        $venta = Venta::findOrFail($ventaId);
        $this->registrarAbono($ventaId, [
            'monto' => $monto,
            'fkmetodo' => $metodoPagoId,
            'fksede' => $venta->fksede,
            'fkuser' => auth()->id() ?? $venta->fkusers,
        ]);

        if ($fechaAcordada && $venta->fresh()->saldo > 0) {
            $venta->update(['fecha_acordada' => $fechaAcordada]);
        }

        return $venta->fresh();
    }

    public function calcularSaldo(int $ventaId): float
    {
        $venta = Venta::findOrFail($ventaId);

        return max(0, $venta->venta_total - $venta->monto_pagado);
    }

    public function aplicarPagoACuota(int $cuotaId, float $monto): Cuota
    {
        $cuota = Cuota::findOrFail($cuotaId);

        if ($cuota->fkventa) {
            $venta = $cuota->venta;
            $this->registrarAbono($venta->id_venta, [
                'monto' => $monto,
                'fkmetodo' => $venta->fkmetodo,
                'fksede' => $venta->fksede,
                'fkuser' => auth()->id() ?? $venta->fkusers,
            ]);

            return $cuota->fresh();
        }

        return $this->aplicarPagoACuotaLegacy($cuota, $monto);
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
                'estado_pago' => $totalCuotas >= $venta->venta_total ? 'pendiente' : 'parcial',
                'monto_pagado' => max(0, $venta->venta_total - $totalCuotas),
                'saldo' => $totalCuotas,
            ]);

            return $venta->fresh();
        });
    }

    public function marcarComoVencido(): int
    {
        return Cuota::whereIn('estado', ['pendiente', 'parcial'])
            ->whereDate('fecha_acordada', '<', today())
            ->update(['estado' => 'vencida']);
    }

    private function aplicarMontoACuotas(Venta $venta, float $monto): array
    {
        $restante = $monto;
        $aplicadas = [];
        $cuotas = $venta->cuotas()->whereIn('estado', ['pendiente', 'parcial', 'vencida'])
            ->orderBy('fecha_acordada')->lockForUpdate()->get();

        foreach ($cuotas as $cuota) {
            if ($restante <= 0) {
                break;
            }

            $aplicado = min($restante, (float) $cuota->saldo);
            $pagado = (float) $cuota->monto_pagado + $aplicado;
            $saldo = max(0, (float) $cuota->monto - $pagado);
            $cuota->update([
                'monto_pagado' => $pagado,
                'saldo' => $saldo,
                'estado' => $saldo <= 0 ? 'pagada' : 'parcial',
                'fecha_pago_real' => $saldo <= 0 ? today() : $cuota->fecha_pago_real,
            ]);

            $aplicadas[] = $cuota->id_cuota;
            $restante -= $aplicado;
        }

        return $aplicadas;
    }

    private function aplicarPagoACuotaLegacy(Cuota $cuota, float $monto): Cuota
    {
        return DB::transaction(function () use ($cuota, $monto) {
            $pagado = min((float) $cuota->monto, (float) $cuota->monto_pagado + $monto);
            $saldo = max(0, (float) $cuota->monto - $pagado);
            $cuota->update([
                'monto_pagado' => $pagado,
                'saldo' => $saldo,
                'estado' => $saldo <= 0 ? 'pagada' : 'parcial',
                'fecha_pago_real' => $saldo <= 0 ? today() : $cuota->fecha_pago_real,
            ]);

            if ($cuota->fkpago) {
                $pago = Pago::find($cuota->fkpago);
                if ($pago) {
                    $saldoPago = $pago->cuotas()->sum('saldo');
                    $pago->update([
                        'saldo' => $saldoPago,
                        'monto_pagado' => max(0, $pago->total - $saldoPago),
                        'estado_pago' => $saldoPago <= 0 ? 'completo' : 'incompleto',
                    ]);
                }
            }

            return $cuota->fresh();
        });
    }

    private function auditarAbono(Abono $abono): void
    {
        $this->auditService->registrarCreacion('cobranza', 'Abono', $abono->id_abono, $abono->toArray(), $abono->fkuser);
    }
}
