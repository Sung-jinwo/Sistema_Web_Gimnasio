<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\Comision;
use App\Models\Gasto;
use App\Models\MovimientoCaja;
use App\Models\Pago;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class CashClosingService
{
    public function obtenerOperaciones(Caja $caja): array
    {
        $fechaApertura = $caja->fecha_apertura;
        $fechaCierre = $caja->fecha_cierre ?? now();
        $sedeId = $caja->fksede;

        return [
            'ventas' => $this->obtenerVentas($sedeId, $fechaApertura, $fechaCierre),
            'pagos' => $this->obtenerPagos($sedeId, $fechaApertura, $fechaCierre),
            'gastos' => $this->obtenerGastosAprobados($sedeId, $fechaApertura, $fechaCierre),
            'comisiones' => $this->obtenerComisiones($caja->id_caja),
            'movimientos' => $this->obtenerMovimientos($caja->id_caja),
        ];
    }

    public function calcularVentas(Caja $caja): array
    {
        $ventas = $this->obtenerVentas(
            $caja->fksede,
            $caja->fecha_apertura,
            $caja->fecha_cierre ?? now()
        );

        return [
            'cantidad' => $ventas->count(),
            'total' => $ventas->sum('venta_total'),
            'cobrado' => $ventas->sum('monto_pagado'),
            'ventas' => $ventas,
        ];
    }

    public function calcularPagos(Caja $caja): array
    {
        $pagos = $this->obtenerPagos(
            $caja->fksede,
            $caja->fecha_apertura,
            $caja->fecha_cierre ?? now()
        );

        $porMetodo = [];
        foreach ($pagos as $pago) {
            $metodoNombre = $pago->metodo->metod_nombre ?? 'Sin método';
            if (! isset($porMetodo[$metodoNombre])) {
                $porMetodo[$metodoNombre] = 0;
            }
            $porMetodo[$metodoNombre] += $pago->pag_monto;
        }

        return [
            'cantidad' => $pagos->count(),
            'total' => $pagos->sum('pag_monto'),
            'por_metodo' => $porMetodo,
            'pagos' => $pagos,
        ];
    }

    public function calcularGastosAprobados(Caja $caja): array
    {
        $gastos = $this->obtenerGastosAprobados(
            $caja->fksede,
            $caja->fecha_apertura,
            $caja->fecha_cierre ?? now()
        );

        return [
            'cantidad' => $gastos->count(),
            'total' => $gastos->sum('gas_monto'),
            'gastos' => $gastos,
        ];
    }

    public function calcularComisiones(Caja $caja): array
    {
        $comisiones = $this->obtenerComisiones($caja->id_caja);

        return [
            'cantidad' => $comisiones->count(),
            'total_base' => $comisiones->sum('comision_base'),
            'total_penalizaciones' => $comisiones->sum('penalizacion'),
            'total_final' => $comisiones->sum('comision_final'),
            'comisiones' => $comisiones,
        ];
    }

    public function calcularMontoEsperado(Caja $caja): float
    {
        $ventas = $this->calcularVentas($caja);
        $pagos = $this->calcularPagos($caja);
        $gastos = $this->calcularGastosAprobados($caja);

        // Caja cuenta dinero efectivamente cobrado, no cuentas por cobrar.
        $ingresos = $ventas['cobrado'] + $pagos['total'];
        $egresos = $gastos['total'];

        return $caja->monto_inicial + $ingresos - $egresos;
    }

    public function calcularDiferencia(float $montoEsperado, float $montoEntregado): float
    {
        return $montoEsperado - $montoEntregado;
    }

    public function cerrarCaja(Caja $caja, float $montoEntregado): Caja
    {
        return DB::transaction(function () use ($caja, $montoEntregado) {
            $montoEsperado = $this->calcularMontoEsperado($caja);
            $diferencia = $this->calcularDiferencia($montoEsperado, $montoEntregado);

            $ventas = $this->calcularVentas($caja);
            $pagos = $this->calcularPagos($caja);
            $gastos = $this->calcularGastosAprobados($caja);

            $caja->update([
                'fecha_cierre' => now(),
                'monto_final' => $montoEntregado,
                'total_ingresos_esperado' => $montoEsperado,
                'total_egresos' => $gastos['total'],
                'monto_entregado' => $montoEntregado,
                'diferencia' => $diferencia,
                'estado' => 'cerrada',
            ]);

            $this->generarComisionesCierre($caja);

            return $caja->fresh();
        });
    }

    public function generarPdf(Caja $caja): \Barryvdh\DomPDF\PDF
    {
        $operaciones = $this->obtenerOperaciones($caja);
        $ventas = $this->calcularVentas($caja);
        $pagos = $this->calcularPagos($caja);
        $gastos = $this->calcularGastosAprobados($caja);
        $comisiones = $this->calcularComisiones($caja);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('caja.pdf.cierre', [
            'caja' => $caja,
            'operaciones' => $operaciones,
            'ventas' => $ventas,
            'pagos' => $pagos,
            'gastos' => $gastos,
            'comisiones' => $comisiones,
        ]);

        return $pdf;
    }

    protected function obtenerVentas(int $sedeId, $fechaApertura, $fechaCierre)
    {
        return Venta::where('fksede', $sedeId)
            ->where('estado_venta', 'completado')
            ->whereBetween('created_at', [$fechaApertura, $fechaCierre])
            ->with(['alumno', 'producto', 'user'])
            ->get();
    }

    protected function obtenerPagos(int $sedeId, $fechaApertura, $fechaCierre)
    {
        return Pago::where('fksede', $sedeId)
            ->whereIn('estado_pago', ['completo', 'incompleto'])
            ->whereBetween('created_at', [$fechaApertura, $fechaCierre])
            ->with(['alumno', 'membresia', 'metodo', 'user'])
            ->get();
    }

    protected function obtenerGastosAprobados(int $sedeId, $fechaApertura, $fechaCierre)
    {
        return Gasto::where('fksede', $sedeId)
            ->where('estado', 'aprobado')
            ->whereBetween('gas_fecha', [$fechaApertura, $fechaCierre])
            ->with(['categoria', 'user'])
            ->get();
    }

    protected function obtenerComisiones(int $cajaId)
    {
        return Comision::where('fkcaja', $cajaId)
            ->with(['usuario', 'venta'])
            ->get();
    }

    protected function obtenerMovimientos(int $cajaId)
    {
        return MovimientoCaja::where('fkcaja', $cajaId)
            ->with(['usuario'])
            ->orderBy('created_at')
            ->get();
    }

    protected function generarComisionesCierre(Caja $caja): void
    {
        $ventas = $this->obtenerVentas(
            $caja->fksede,
            $caja->fecha_apertura,
            $caja->fecha_cierre
        );

        $pagos = $this->obtenerPagos(
            $caja->fksede,
            $caja->fecha_apertura,
            $caja->fecha_cierre
        );

        $commissionService = app(CommissionService::class);

        foreach ($ventas as $venta) {
            $comisionExistente = Comision::where('fkventa', $venta->id_venta)->first();

            if (! $comisionExistente) {
                $comisionBase = $commissionService->calcularComisionBase($venta->id_venta, $venta->fkusers);

                if ($comisionBase > 0) {
                    $commissionService->guardarComision($venta->id_venta, $venta->fkusers, $comisionBase);
                    Comision::where('fkventa', $venta->id_venta)->update(['fkcaja' => $caja->id_caja]);
                }
            }
        }

        foreach ($pagos as $pago) {
            if ($pago->membresia) {
                $comisionBase = $pago->pag_monto * 0.10;

                if ($comisionBase > 0) {
                    Comision::create([
                        'fkuser' => $pago->fkuser,
                        'fkcaja' => $caja->id_caja,
                        'tipo' => 'membresia',
                        'monto' => $comisionBase,
                        'comision_base' => $comisionBase,
                        'penalizacion' => 0,
                        'comision_final' => $comisionBase,
                        'estado' => 'pendiente',
                    ]);
                }
            }
        }
    }
}
