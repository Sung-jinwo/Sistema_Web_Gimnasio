<?php

namespace App\Services;

use Carbon\Carbon;

class PenaltyService
{
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

        if ($diasRetraso < 7) {
            return [
                'dias_retraso' => max(0, $diasRetraso),
                'semanas_retraso' => 0,
                'penalizacion' => 0,
                'comision_final' => $comisionBase,
            ];
        }

        $semanasRetraso = (int) floor($diasRetraso / 7);
        $penalizacion = $semanasRetraso * self::PENALIZACION_POR_SEMANA;
        $comisionFinal = max(0, $comisionBase - $penalizacion);

        return [
            'dias_retraso' => $diasRetraso,
            'semanas_retraso' => $semanasRetraso,
            'penalizacion' => $penalizacion,
            'comision_final' => $comisionFinal,
        ];
    }
}
