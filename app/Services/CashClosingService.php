<?php

namespace App\Services;

use App\Models\Abono;
use App\Models\Caja;
use App\Models\CajaCierreDetalle;
use App\Models\Comision;
use App\Models\Gasto;
use App\Models\MetodoPago;
use App\Models\MovimientoCaja;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashClosingService
{
    public function __construct(private readonly AuditService $auditService) {}

    public function marcarVencidas(?int $userId = null): int
    {
        $query = Caja::where('estado', 'abierta');
        if ($userId) {
            $query->where('fkuser', $userId);
        }

        $cantidad = 0;
        $query->get()->each(function (Caja $caja) use (&$cantidad) {
            if (! $this->vencio($caja)) {
                return;
            }

            $anteriores = $caja->toArray();
            $caja->update(['estado' => 'pendiente_cierre']);
            $this->auditService->registrarEdicion('caja', 'Caja', $caja->id_caja, $anteriores, $caja->fresh()->toArray(), $caja->fkuser);
            $cantidad++;
        });

        return $cantidad;
    }

    public function cajaOperativaDe(int $userId): ?Caja
    {
        $this->marcarVencidas($userId);

        return Caja::where('fkuser', $userId)
            ->where('estado', 'abierta')
            ->whereDate('fecha_operativa', today())
            ->first();
    }

    public function impedimentoAperturaDe(int $userId): ?string
    {
        $this->marcarVencidas($userId);

        if (Caja::where('fkuser', $userId)->where('estado', 'pendiente_cierre')->exists()) {
            return 'Debes enviar el cierre de tu caja anterior antes de abrir una nueva.';
        }

        if (Caja::where('fkuser', $userId)
            ->whereDate('fecha_operativa', today())
            ->whereNotIn('estado', ['anulada'])
            ->exists()) {
            return 'Ya registraste una caja para el día operativo actual.';
        }

        return null;
    }

    public function enviarCierre(Caja $caja, array $declarados, ?string $observacion = null): Caja
    {
        return DB::transaction(function () use ($caja, $declarados, $observacion) {
            $caja = Caja::lockForUpdate()->findOrFail($caja->id_caja);
            if (! in_array($caja->estado, ['abierta', 'pendiente_cierre', 'observada'], true)) {
                throw ValidationException::withMessages(['caja' => 'La caja ya no admite un envío de cierre.']);
            }

            $metodos = MetodoPago::orderBy('id_metod')->get();
            $esperados = $this->calcularEsperadoPorMetodo($caja, $metodos);
            foreach ($metodos as $metodo) {
                if (! array_key_exists($metodo->id_metod, $declarados)) {
                    throw ValidationException::withMessages(['declarados' => 'Debes declarar el importe de todos los métodos de pago.']);
                }
            }

            $this->guardarDetalles($caja, $metodos, $esperados, $declarados);
            $montoEsperado = array_sum($esperados);
            $montoDeclarado = array_sum($declarados);
            $gastos = $this->calcularGastosAprobados($caja);
            $fechaCierre = $caja->fecha_cierre ?? now()->min($this->limiteOperativo($caja));
            $anteriores = $caja->toArray();

            $caja->update([
                'fecha_cierre' => $fechaCierre,
                'enviado_cierre_at' => now(),
                'monto_final' => $montoDeclarado,
                'monto_entregado' => $montoDeclarado,
                'total_ingresos_esperado' => $montoEsperado,
                'total_egresos' => $gastos['total'],
                'diferencia' => $montoEsperado - $montoDeclarado,
                'estado' => 'pendiente_revision',
                'observacion' => $observacion,
                'observacion_revision' => null,
                'revisada_at' => null,
                'revisada_por' => null,
            ]);

            $this->auditService->registrarEdicion('caja', 'Caja', $caja->id_caja, $anteriores, $caja->fresh()->toArray());

            return $caja->fresh(['detallesCierre.metodo']);
        });
    }

    public function aprobarCierre(Caja $caja, int $adminId, ?string $observacion = null): Caja
    {
        return DB::transaction(function () use ($caja, $adminId, $observacion) {
            $caja = Caja::lockForUpdate()->findOrFail($caja->id_caja);
            if ($caja->estado !== 'pendiente_revision') {
                throw ValidationException::withMessages(['caja' => 'Solo se pueden aprobar cajas pendientes de revisión.']);
            }
            if ($this->tieneGastosPendientes($caja)) {
                throw ValidationException::withMessages(['caja' => 'Resuelve los gastos pendientes antes de aprobar el cierre.']);
            }
            if ($this->tieneComisionesBloqueantes($caja)) {
                throw ValidationException::withMessages(['caja' => 'Hay comisiones pendientes de revisión u observadas. Revísalas antes de aprobar el cierre.']);
            }

            $metodos = MetodoPago::orderBy('id_metod')->get();
            $esperados = $this->calcularEsperadoPorMetodo($caja, $metodos);
            $declarados = $caja->detallesCierre()->pluck('monto_declarado', 'fkmetodo')->map(fn ($monto) => (float) $monto)->all();
            $this->guardarDetalles($caja, $metodos, $esperados, $declarados);
            $montoEsperado = array_sum($esperados);
            $montoDeclarado = array_sum($declarados);
            $gastos = $this->calcularGastosAprobados($caja);
            $anteriores = $caja->toArray();

            $caja->update([
                'estado' => 'cerrada',
                'revisada_por' => $adminId,
                'revisada_at' => now(),
                'observacion_revision' => $observacion,
                'total_ingresos_esperado' => $montoEsperado,
                'total_egresos' => $gastos['total'],
                'monto_final' => $montoDeclarado,
                'monto_entregado' => $montoDeclarado,
                'diferencia' => $montoEsperado - $montoDeclarado,
            ]);

            $this->generarComisionesCierre($caja);
            $this->auditService->registrarAprobacion('caja', 'Caja', $caja->id_caja, $caja->fresh()->toArray(), $adminId);

            return $caja->fresh(['detallesCierre.metodo', 'revisadaPor']);
        });
    }

    public function observarCierre(Caja $caja, int $adminId, string $observacion): Caja
    {
        if ($caja->estado !== 'pendiente_revision') {
            throw ValidationException::withMessages(['caja' => 'Solo se pueden observar cajas pendientes de revisión.']);
        }

        $anteriores = $caja->toArray();
        $caja->update([
            'estado' => 'observada',
            'revisada_por' => $adminId,
            'revisada_at' => now(),
            'observacion_revision' => $observacion,
        ]);
        $this->auditService->registrarRechazo('caja', 'Caja', $caja->id_caja, $caja->fresh()->toArray(), $adminId);

        return $caja->fresh(['detallesCierre.metodo', 'revisadaPor']);
    }

    public function metodosParaCierre()
    {
        return MetodoPago::orderByDesc('es_efectivo')->orderBy('metod_nombre')->get();
    }

    public function obtenerOperaciones(Caja $caja): array
    {
        return [
            'ventas' => $this->obtenerVentas($caja),
            'pagos' => $this->obtenerPagos($caja),
            'gastos' => $this->obtenerGastosAprobados($caja),
            'comisiones' => $this->obtenerComisiones($caja->id_caja),
            'movimientos' => $this->obtenerMovimientos($caja->id_caja),
        ];
    }

    public function calcularVentas(Caja $caja): array
    {
        $ventas = $this->obtenerVentas($caja);

        return [
            'cantidad' => $ventas->count(),
            'total' => $ventas->sum('venta_total'),
            'cobrado' => $ventas->sum('monto_pagado'),
            'ventas' => $ventas,
        ];
    }

    public function calcularPagos(Caja $caja): array
    {
        $pagos = $this->obtenerPagos($caja);

        $porMetodo = [];
        foreach ($pagos as $pago) {
            $metodoNombre = $pago->metodo->metod_nombre ?? 'Sin método';
            if (! isset($porMetodo[$metodoNombre])) {
                $porMetodo[$metodoNombre] = 0;
            }
            $porMetodo[$metodoNombre] += $pago->monto;
        }

        return [
            'cantidad' => $pagos->count(),
            'total' => $pagos->sum('monto'),
            'por_metodo' => $porMetodo,
            'pagos' => $pagos,
        ];
    }

    public function calcularGastosAprobados(Caja $caja): array
    {
        $gastos = $this->obtenerGastosAprobados($caja);

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
        $ingresos = $pagos['total'];
        $egresos = $gastos['total'];

        return $caja->monto_inicial + $ingresos - $egresos;
    }

    public function calcularDiferencia(float $montoEsperado, float $montoEntregado): float
    {
        return $montoEsperado - $montoEntregado;
    }

    public function tieneGastosPendientes(Caja $caja): bool
    {
        return Gasto::where('fkcaja', $caja->id_caja)->where('estado', 'pendiente')->exists();
    }

    /**
     * Comisiones habilitadas sin resolver en la caja de revisión.
     * Las anuladas se consideran resueltas; las que esperan pago aún
     * no están habilitadas y tampoco bloquean.
     */
    public function tieneComisionesBloqueantes(Caja $caja): bool
    {
        return Comision::where('fkcaja', $caja->id_caja)
            ->whereIn('estado', Comision::ESTADOS_BLOQUEAN_CIERRE)
            ->exists();
    }

    public function comisionesBloqueantes(Caja $caja)
    {
        return Comision::with(['usuario', 'venta.alumno'])
            ->where('fkcaja', $caja->id_caja)
            ->whereIn('estado', Comision::ESTADOS_BLOQUEAN_CIERRE)
            ->orderBy('id_comision')
            ->get();
    }

    private function calcularEsperadoPorMetodo(Caja $caja, $metodos): array
    {
        $pagos = $this->obtenerPagos($caja);
        $gastos = $this->obtenerGastosAprobados($caja);
        $efectivo = $metodos->firstWhere('es_efectivo', true) ?? $metodos->first();
        $esperados = [];

        foreach ($metodos as $metodo) {
            $ingresos = (float) $pagos->where('fkmetodo', $metodo->id_metod)->sum('monto');
            $egresos = (float) $gastos->filter(function (Gasto $gasto) use ($metodo, $efectivo) {
                if ($gasto->fkmetodo) {
                    return (int) $gasto->fkmetodo === (int) $metodo->id_metod;
                }

                return (int) $metodo->id_metod === (int) $efectivo?->id_metod;
            })->sum('gas_monto');
            $inicial = $metodo->es_efectivo ? (float) $caja->monto_inicial : 0;
            $esperados[$metodo->id_metod] = round($inicial + $ingresos - $egresos, 2);
        }

        return $esperados;
    }

    private function guardarDetalles(Caja $caja, $metodos, array $esperados, array $declarados): void
    {
        foreach ($metodos as $metodo) {
            $esperado = (float) ($esperados[$metodo->id_metod] ?? 0);
            $declarado = (float) ($declarados[$metodo->id_metod] ?? 0);
            CajaCierreDetalle::updateOrCreate(
                ['fkcaja' => $caja->id_caja, 'fkmetodo' => $metodo->id_metod],
                [
                    'monto_esperado' => $esperado,
                    'monto_declarado' => $declarado,
                    'diferencia' => $esperado - $declarado,
                ]
            );
        }
    }

    private function vencio(Caja $caja): bool
    {
        return now()->greaterThan($this->limiteOperativo($caja));
    }

    private function limiteOperativo(Caja $caja): Carbon
    {
        $fecha = $caja->fecha_operativa?->toDateString() ?? $caja->fecha_apertura->toDateString();

        return Carbon::parse($fecha.' '.config('caja.hora_corte', '23:59'), config('app.timezone'))->endOfMinute();
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

        $caja->loadMissing(['detallesCierre.metodo', 'revisadaPor']);
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

    /**
     * Operaciones vinculadas a la caja: primero por fkcaja directo;
     * como respaldo, registros históricos sin vincular dentro de la
     * misma sede y ventana de apertura/cierre.
     */
    protected function alcanceCaja($query, Caja $caja, string $colFecha)
    {
        $cierre = $caja->fecha_cierre ?? now();

        return $query->where(function ($w) use ($caja, $colFecha, $cierre) {
            $w->where('fkcaja', $caja->id_caja)
                ->orWhere(function ($legacy) use ($caja, $colFecha, $cierre) {
                    $legacy->whereNull('fkcaja')
                        ->where('fksede', $caja->fksede)
                        ->whereBetween($colFecha, [$caja->fecha_apertura, $cierre]);
                });
        });
    }

    protected function obtenerVentas(Caja $caja)
    {
        return $this->alcanceCaja(
            Venta::where('estado_venta', 'completado'),
            $caja,
            'created_at'
        )->with(['alumno', 'producto', 'user'])->get();
    }

    protected function obtenerPagos(Caja $caja)
    {
        return $this->alcanceCaja(
            Abono::query(),
            $caja,
            'fecha_abono'
        )->with(['venta.alumno', 'venta.membresiaAlumno.membresia', 'metodo', 'user'])->get();
    }

    protected function obtenerGastosAprobados(Caja $caja)
    {
        return $this->alcanceCaja(
            Gasto::where('estado', 'aprobado'),
            $caja,
            'gas_fecha'
        )->with(['categoria', 'user', 'metodo'])->get();
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
        $ventas = $this->obtenerVentas($caja);

        $commissionService = app(CommissionService::class);

        foreach ($ventas as $venta) {
            $comisionExistente = Comision::where('fkventa', $venta->id_venta)->first();

            if (! $comisionExistente) {
                $comisionBase = $commissionService->calcularComisionBase($venta->id_venta, $venta->fkusers);

                if ($comisionBase > 0) {
                    $nueva = $commissionService->guardarComision($venta->id_venta, $venta->fkusers, $comisionBase);

                    if ($nueva->estado === Comision::ESTADO_PENDIENTE_REVISION) {
                        $nueva->update(['fkcaja' => $caja->id_caja]);
                    }
                }
            }
        }

    }
}
