<?php

namespace App\Services;

use App\Models\Comision;
use App\Models\MembresiaAlumno;
use App\Models\Venta;

class CommissionService
{
    protected PenaltyService $penaltyService;

    public function __construct(PenaltyService $penaltyService)
    {
        $this->penaltyService = $penaltyService;
    }

    public function calcularComisionBase(int $ventaId, int $usuarioId): float
    {
        $venta = Venta::with('producto')->findOrFail($ventaId);

        if ($venta->tipo_venta === 'membresia') {
            $membresiaAlumno = MembresiaAlumno::where('fkalumno', $venta->fkalum)
                ->latest('fecha_inicio')
                ->first();

            return $membresiaAlumno ? (float) $membresiaAlumno->comision_aplicada : 0;
        }

        if ($venta->tipo_venta === 'producto' || $venta->tipo_venta === 'rapida') {
            if ($venta->producto) {
                return (float) $venta->producto->prod_precio * 0.10;
            }
        }

        return 0;
    }

    public function guardarComision(int $ventaId, int $usuarioId, float $montoBase, ?string $fechaAcordada = null): Comision
    {
        return Comision::create([
            'fkuser' => $usuarioId,
            'fkventa' => $ventaId,
            'monto' => $montoBase,
            'comision_base' => $montoBase,
            'penalizacion' => 0,
            'comision_final' => $montoBase,
            'tipo' => $this->obtenerTipoComision($ventaId),
            'estado' => 'pendiente',
            'fecha_acordada_pago' => $fechaAcordada,
        ]);
    }

    public function calcularComisionFinal(int $comisionId): array
    {
        $comision = Comision::findOrFail($comisionId);

        return $this->penaltyService->calcularPenalizacion(
            $comision->fecha_acordada_pago,
            $comision->fecha_pago_real,
            $comision->comision_base
        );
    }

    public function registrarPagoComision(int $comisionId): Comision
    {
        $comision = Comision::findOrFail($comisionId);

        $comision->update([
            'fecha_pago_real' => now()->format('Y-m-d'),
            'estado' => 'liquidada',
        ]);

        $this->penaltyService->aplicarPenalizacion($comisionId);

        return $comision->fresh();
    }

    public function obtenerComisionesPorCaja(int $cajaId): array
    {
        $comisiones = Comision::where('fkcaja', $cajaId)->get();

        $totalBase = $comisiones->sum('comision_base');
        $totalPenalizaciones = $comisiones->sum('penalizacion');
        $totalFinal = $comisiones->sum('comision_final');

        return [
            'comisiones' => $comisiones,
            'total_base' => $totalBase,
            'total_penalizaciones' => $totalPenalizaciones,
            'total_final' => $totalFinal,
            'cantidad' => $comisiones->count(),
        ];
    }

    protected function obtenerTipoComision(int $ventaId): string
    {
        $venta = Venta::find($ventaId);

        return $venta && $venta->tipo_venta === 'membresia' ? 'membresia' : 'venta';
    }
}
