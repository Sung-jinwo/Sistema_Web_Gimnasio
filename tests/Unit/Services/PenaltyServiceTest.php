<?php

namespace Tests\Unit\Services;

use App\Services\PenaltyService;
use PHPUnit\Framework\TestCase;

class PenaltyServiceTest extends TestCase
{
    protected PenaltyService $penaltyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->penaltyService = new PenaltyService;
    }

    public function test_no_penalty_within_tolerance_period(): void
    {
        $fechaAcordada = '2024-01-01';
        $fechaPagoReal = '2024-01-05';
        $comisionBase = 100.00;

        $resultado = $this->penaltyService->calcularPenalizacion(
            $fechaAcordada,
            $fechaPagoReal,
            $comisionBase
        );

        $this->assertEquals(4, $resultado['dias_retraso']);
        $this->assertEquals(0, $resultado['semanas_retraso']);
        $this->assertEquals(0, $resultado['penalizacion']);
        $this->assertEquals(100.00, $resultado['comision_final']);
    }

    public function test_no_penalty_exactly_on_tolerance_day(): void
    {
        $fechaAcordada = '2024-01-01';
        $fechaPagoReal = '2024-01-08';
        $comisionBase = 100.00;

        $resultado = $this->penaltyService->calcularPenalizacion(
            $fechaAcordada,
            $fechaPagoReal,
            $comisionBase
        );

        $this->assertEquals(7, $resultado['dias_retraso']);
        $this->assertEquals(0, $resultado['semanas_retraso']);
        $this->assertEquals(0, $resultado['penalizacion']);
        $this->assertEquals(100.00, $resultado['comision_final']);
    }

    public function test_penalty_after_tolerance_period_one_week(): void
    {
        $fechaAcordada = '2024-01-01';
        $fechaPagoReal = '2024-01-15';
        $comisionBase = 100.00;

        $resultado = $this->penaltyService->calcularPenalizacion(
            $fechaAcordada,
            $fechaPagoReal,
            $comisionBase
        );

        $this->assertEquals(14, $resultado['dias_retraso']);
        $this->assertEquals(1, $resultado['semanas_retraso']);
        $this->assertEquals(5.00, $resultado['penalizacion']);
        $this->assertEquals(95.00, $resultado['comision_final']);
    }

    public function test_penalty_after_tolerance_period_two_weeks(): void
    {
        $fechaAcordada = '2024-01-01';
        $fechaPagoReal = '2024-01-22';
        $comisionBase = 100.00;

        $resultado = $this->penaltyService->calcularPenalizacion(
            $fechaAcordada,
            $fechaPagoReal,
            $comisionBase
        );

        $this->assertEquals(21, $resultado['dias_retraso']);
        $this->assertEquals(2, $resultado['semanas_retraso']);
        $this->assertEquals(10.00, $resultado['penalizacion']);
        $this->assertEquals(90.00, $resultado['comision_final']);
    }

    public function test_penalty_cannot_exceed_commission(): void
    {
        $fechaAcordada = '2024-01-01';
        $fechaPagoReal = '2024-02-01';
        $comisionBase = 10.00;

        $resultado = $this->penaltyService->calcularPenalizacion(
            $fechaAcordada,
            $fechaPagoReal,
            $comisionBase
        );

        $this->assertGreaterThan(0, $resultado['dias_retraso']);
        $this->assertEquals(0, $resultado['comision_final']);
    }

    public function test_penalty_with_partial_week(): void
    {
        $fechaAcordada = '2024-01-01';
        $fechaPagoReal = '2024-01-12';
        $comisionBase = 100.00;

        $resultado = $this->penaltyService->calcularPenalizacion(
            $fechaAcordada,
            $fechaPagoReal,
            $comisionBase
        );

        $this->assertEquals(11, $resultado['dias_retraso']);
        $this->assertEquals(1, $resultado['semanas_retraso']);
        $this->assertEquals(5.00, $resultado['penalizacion']);
        $this->assertEquals(95.00, $resultado['comision_final']);
    }

    public function test_no_penalty_when_paid_on_time(): void
    {
        $fechaAcordada = '2024-01-10';
        $fechaPagoReal = '2024-01-10';
        $comisionBase = 50.00;

        $resultado = $this->penaltyService->calcularPenalizacion(
            $fechaAcordada,
            $fechaPagoReal,
            $comisionBase
        );

        $this->assertEquals(0, $resultado['dias_retraso']);
        $this->assertEquals(0, $resultado['penalizacion']);
        $this->assertEquals(50.00, $resultado['comision_final']);
    }

    public function test_no_penalty_when_paid_early(): void
    {
        $fechaAcordada = '2024-01-10';
        $fechaPagoReal = '2024-01-05';
        $comisionBase = 50.00;

        $resultado = $this->penaltyService->calcularPenalizacion(
            $fechaAcordada,
            $fechaPagoReal,
            $comisionBase
        );

        $this->assertEquals(0, $resultado['penalizacion']);
        $this->assertEquals(50.00, $resultado['comision_final']);
    }

    public function test_handles_null_dates_gracefully(): void
    {
        $comisionBase = 100.00;

        $resultado = $this->penaltyService->calcularPenalizacion(
            null,
            null,
            $comisionBase
        );

        $this->assertEquals(0, $resultado['penalizacion']);
        $this->assertEquals(100.00, $resultado['comision_final']);
    }
}
