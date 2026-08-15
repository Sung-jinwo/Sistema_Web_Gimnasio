<?php

namespace App\Services;

use Carbon\Carbon;

class PenaltyService
{
    const TOLERANCIA_DIAS = 7;

    const PENALIZACION_POR_SEMANA = 5.00;

    public function calcularPenalizacion(?string $fechaAcordada, ?string $fechaPagoReal, float $comisionBase): array
    {
        if (! $fechaAcordada || ! $fechaPagoReal) {
            return [
                'dias_retraso' => 0,
                'semanas_retraso' => 0,
                'penalizacion' => 0,
                'comision_final' => $comisionBase,
            ];
        }

        $fechaAcordadaCarbon = Carbon::parse($fechaAcordada);
        $fechaPagoRealCarbon = Carbon::parse($fechaPagoReal);

        $diasRetraso = $fechaAcordadaCarbon->diffInDays($fechaPagoRealCarbon, false);

        if ($diasRetraso <= self::TOLERANCIA_DIAS) {
            return [
                'dias_retraso' => max(0, $diasRetraso),
                'semanas_retraso' => 0,
                'penalizacion' => 0,
                'comision_final' => $comisionBase,
            ];
        }

        $diasFueraTolerancia = $diasRetraso - self::TOLERANCIA_DIAS;
        $semanasRetraso = (int) ceil($diasFueraTolerancia / 7);
        $penalizacion = $semanasRetraso * self::PENALIZACION_POR_SEMANA;
        $comisionFinal = max(0, $comisionBase - $penalizacion);

        return [
            'dias_retraso' => $diasRetraso,
            'semanas_retraso' => $semanasRetraso,
            'penalizacion' => $penalizacion,
            'comision_final' => $comisionFinal,
        ];
    }

    public function aplicarPenalizacion(int $comisionId): void
    {
        $comision = \App\Models\Comision::findOrFail($comisionId);

        $resultado = $this->calcularPenalizacion(
            $comision->fecha_acordada_pago,
            $comision->fecha_pago_real,
            $comision->comision_base
        );

        $comision->update([
            'penalizacion' => $resultado['penalizacion'],
            'comision_final' => $resultado['comision_final'],
        ]);
    }
}
