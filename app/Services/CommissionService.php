<?php

namespace App\Services;

use App\Models\Comision;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommissionService
{
    protected PenaltyService $penaltyService;

    protected AuditService $auditService;

    public function __construct(PenaltyService $penaltyService, AuditService $auditService)
    {
        $this->penaltyService = $penaltyService;
        $this->auditService = $auditService;
    }

    public function calcularComisionBase(int $ventaId, int $usuarioId): float
    {
        $venta = Venta::with(['membresia', 'detalles.producto'])->findOrFail($ventaId);

        if ($venta->tipo_venta === 'membresia') {
            return (float) ($venta->membresia?->comision ?? $venta->membresiaAlumno?->comision_aplicada ?? 0);
        }

        if ($venta->tipo_venta === 'producto' || $venta->tipo_venta === 'rapida') {
            if ($venta->detalles->isNotEmpty()) {
                return (float) $venta->detalles->sum(fn ($detalle) => (float) ($detalle->producto?->comision ?? 0) * $detalle->cantidad);
            }

            return (float) ($venta->producto?->comision ?? 0);
        }

        return 0;
    }

    /**
     * Crea la comisión en el estado que corresponda: las ventas pagadas
     * quedan listas para revisión en su caja; las que tienen deuda esperan.
     */
    public function guardarComision(int $ventaId, int $usuarioId, float $montoBase, ?string $fechaAcordada = null): Comision
    {
        $venta = Venta::findOrFail($ventaId);
        $pagada = (float) $venta->saldo <= 0;

        return Comision::create([
            'fkuser' => $usuarioId,
            'fkventa' => $ventaId,
            'fkcaja' => $pagada ? $venta->fkcaja : null,
            'monto' => $montoBase,
            'comision_base' => $montoBase,
            'penalizacion' => 0,
            'comision_final' => $montoBase,
            'tipo' => $this->obtenerTipoComision($ventaId),
            'estado' => $pagada ? Comision::ESTADO_PENDIENTE_REVISION : Comision::ESTADO_ESPERANDO_PAGO,
            'fecha_habilitacion' => $pagada ? now() : null,
            'fecha_acordada_pago' => $fechaAcordada,
        ]);
    }

    /**
     * Habilita la comisión al registrarse el último abono: congela la
     * penalización y el importe final (ya recalculados) y la vincula a
     * la caja del abono, conservando la fecha acordada como referencia.
     */
    public function habilitarComisionPorCobroFinal(Venta $venta, ?int $cajaId, ?string $fechaAcordadaPrevia = null): void
    {
        $comision = $venta->comisiones()->where('estado', Comision::ESTADO_ESPERANDO_PAGO)->first();

        if (! $comision) {
            return;
        }

        $comision->update([
            'estado' => Comision::ESTADO_PENDIENTE_REVISION,
            'fkcaja' => $cajaId ?? $venta->fkcaja,
            'fecha_habilitacion' => now(),
            'fecha_acordada_pago' => $fechaAcordadaPrevia ?? $comision->fecha_acordada_pago,
        ]);
    }

    public function aprobarComision(int $comisionId, int $adminId): Comision
    {
        return DB::transaction(function () use ($comisionId, $adminId) {
            $comision = Comision::lockForUpdate()->findOrFail($comisionId);

            if (! in_array($comision->estado, [Comision::ESTADO_PENDIENTE_REVISION, Comision::ESTADO_OBSERVADA], true)) {
                throw ValidationException::withMessages(['comision' => 'Solo se pueden aprobar comisiones pendientes de revisión u observadas.']);
            }

            $this->exigirCajaRevisable($comision);

            $comision->update([
                'estado' => Comision::ESTADO_APROBADA,
                'aprobada_por' => $adminId,
                'fecha_aprobacion' => now(),
                'motivo_observacion' => null,
            ]);

            $this->auditService->registrarAprobacion('comisiones', 'Comision', $comision->id_comision, $comision->fresh()->toArray(), $adminId);

            return $comision->fresh();
        });
    }

    public function observarComision(int $comisionId, int $adminId, string $motivo): Comision
    {
        return DB::transaction(function () use ($comisionId, $adminId, $motivo) {
            $comision = Comision::lockForUpdate()->findOrFail($comisionId);

            if (! in_array($comision->estado, [Comision::ESTADO_PENDIENTE_REVISION, Comision::ESTADO_APROBADA], true)) {
                throw ValidationException::withMessages(['comision' => 'Solo se pueden observar comisiones pendientes de revisión o aprobadas.']);
            }

            $this->exigirCajaRevisable($comision);

            $comision->update([
                'estado' => Comision::ESTADO_OBSERVADA,
                'motivo_observacion' => $motivo,
                'aprobada_por' => null,
                'fecha_aprobacion' => null,
            ]);

            $this->auditService->registrarRechazo('comisiones', 'Comision', $comision->id_comision, $comision->fresh()->toArray(), $adminId);

            return $comision->fresh();
        });
    }

    /**
     * Solo se revisa si su caja está en revisión; las de cajas ya
     * cerradas/anuladas son la excepción histórica de transición.
     * Sin caja vinculada también se permite (histórico sin traza).
     */
    protected function exigirCajaRevisable(Comision $comision): void
    {
        if (! $comision->fkcaja) {
            return;
        }

        $caja = \App\Models\Caja::find($comision->fkcaja);

        if ($caja && in_array($caja->estado, ['abierta', 'pendiente_cierre', 'observada'], true)) {
            throw ValidationException::withMessages(['comision' => 'La caja de esta comisión aún no está en revisión.']);
        }
    }

    public function anularComisionPorVenta(Venta $venta, ?int $userId = null): void
    {
        $comisiones = $venta->comisiones()->where('estado', '!=', Comision::ESTADO_LIQUIDADA)->get();

        foreach ($comisiones as $comision) {
            $anterior = $comision->toArray();
            $comision->update(['estado' => Comision::ESTADO_ANULADA]);
            $this->auditService->registrarEdicion('comisiones', 'Comision', $comision->id_comision, $anterior, $comision->fresh()->toArray(), $userId ?? auth()->id());
        }
    }

    public function calcularComisionFinal(int $comisionId): array
    {
        $comision = Comision::with('venta')->findOrFail($comisionId);

        return $this->penaltyService->calcularPenalizacion(
            $comision->venta?->fecha_acordada?->format('Y-m-d') ?? $comision->fecha_acordada_pago,
            $this->fechaReferenciaCobro($comision->venta),
            $comision->comision_base
        );
    }

    /**
     * Liquida una comisión aprobada: registra el pago real con método,
     * referencia y observación. No toca ninguna caja conciliada.
     */
    public function registrarPagoComision(int $comisionId, array $datos = []): Comision
    {
        return DB::transaction(function () use ($comisionId, $datos) {
            $comision = Comision::lockForUpdate()->findOrFail($comisionId);

            if ($comision->estado !== Comision::ESTADO_APROBADA) {
                throw ValidationException::withMessages(['comision' => 'Solo se pueden liquidar comisiones aprobadas.']);
            }

            if ($comision->venta && (float) $comision->venta->saldo > 0) {
                throw ValidationException::withMessages(['comision' => 'No se puede liquidar una comisión mientras la venta tenga saldo pendiente.']);
            }

            $comision->update([
                'fecha_pago_real' => now()->format('Y-m-d'),
                'estado' => Comision::ESTADO_LIQUIDADA,
            ]);

            $liquidacionId = DB::table('liquidaciones_comision')->insertGetId([
                'fkuser' => $comision->fkuser,
                'liquidada_por' => $datos['liquidada_por'] ?? auth()->id(),
                'total' => $comision->comision_final,
                'fkmetodo' => $datos['fkmetodo'] ?? null,
                'referencia' => $datos['referencia'] ?? null,
                'observacion' => $datos['observacion'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'id_liquidacion');
            DB::table('liquidacion_comision_detalles')->insert([
                'fkliquidacion' => $liquidacionId,
                'fkcomision' => $comision->id_comision,
            ]);

            $this->auditService->registrarCreacion('comisiones', 'LiquidacionComision', (int) $liquidacionId, [
                'fkuser' => $comision->fkuser,
                'total' => $comision->comision_final,
                'fkcomision' => $comision->id_comision,
            ], $datos['liquidada_por'] ?? auth()->id());

            return $comision->fresh();
        });
    }

    /**
     * Recalcula la penalización solo mientras la comisión sea mutable
     * (en espera o pendiente de revisión). Aprobadas en adelante se congelan.
     */
    public function actualizarPenalizacionVenta(Venta $venta): void
    {
        $comision = $venta->comisiones()->whereIn('estado', Comision::ESTADOS_MUTABLES)->first();
        if (! $comision) {
            return;
        }

        $fechaAcordada = $venta->fecha_acordada?->format('Y-m-d') ?? $comision->fecha_acordada_pago;
        $resultado = $this->penaltyService->calcularPenalizacion(
            $fechaAcordada,
            $this->fechaReferenciaCobro($venta),
            (float) $comision->comision_base
        );
        $comision->update([
            'penalizacion' => $resultado['penalizacion'],
            'comision_final' => $resultado['comision_final'],
        ]);
    }

    public function actualizarPenalizacionesPendientes(): int
    {
        $cantidad = 0;
        Comision::with('venta')->whereIn('estado', Comision::ESTADOS_MUTABLES)->orderBy('id_comision')->each(function (Comision $comision) use (&$cantidad) {
            if ($comision->venta?->fecha_acordada) {
                $this->actualizarPenalizacionVenta($comision->venta);
                $cantidad++;
            }
        });

        return $cantidad;
    }

    private function fechaReferenciaCobro(?Venta $venta): ?string
    {
        if (! $venta) {
            return null;
        }

        return $venta->pagada_at?->format('Y-m-d') ?? today()->format('Y-m-d');
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
