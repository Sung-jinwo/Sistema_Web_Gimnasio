<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\MembresiaAjusteVigencia;
use App\Models\MembresiaAlumno;
use App\Models\MembresiaCongelamiento;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CongelamientoService
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * Programa un congelamiento: extiende el vencimiento de inmediato por
     * los días inclusivos y consume la única oportunidad (salvo cancelación
     * previa al inicio).
     */
    public function programar(int $membresiaAlumnoId, string $inicio, string $fin, string $motivo, int $adminId): MembresiaCongelamiento
    {
        return DB::transaction(function () use ($membresiaAlumnoId, $inicio, $fin, $motivo, $adminId) {
            $membresia = MembresiaAlumno::lockForUpdate()->findOrFail($membresiaAlumnoId);
            $hoy = today()->format('Y-m-d');

            if (! $membresia->membresia?->permite_congelamiento) {
                throw ValidationException::withMessages(['plan' => 'Este plan no permite congelamiento.']);
            }
            if ($membresia->estado !== 'activa' || $membresia->fecha_fin < $hoy) {
                throw ValidationException::withMessages(['membresia' => 'Solo se puede congelar una membresía vigente.']);
            }
            if ($inicio < $hoy) {
                throw ValidationException::withMessages(['fecha_inicio' => 'El inicio debe ser hoy o una fecha futura.']);
            }
            if ($fin < $inicio) {
                throw ValidationException::withMessages(['fecha_fin' => 'La fecha final debe ser posterior a la inicial.']);
            }
            if ($inicio > $membresia->fecha_fin || $fin > $membresia->fecha_fin) {
                throw ValidationException::withMessages(['rango' => 'El rango debe estar dentro de la vigencia restante.']);
            }
            if ($membresia->congelamientos()->whereIn('estado', [MembresiaCongelamiento::ESTADO_PROGRAMADO, MembresiaCongelamiento::ESTADO_ACTIVO, MembresiaCongelamiento::ESTADO_FINALIZADO])->exists()) {
                throw ValidationException::withMessages(['membresia' => 'Esta membresía ya consumió su único congelamiento.']);
            }

            $dias = Carbon::parse($inicio)->diffInDays(Carbon::parse($fin)) + 1;

            $congelamiento = MembresiaCongelamiento::create([
                'fkmembresia_alumno' => $membresia->id_membresia_alumno,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'dias' => $dias,
                'motivo' => $motivo,
                'estado' => $inicio <= $hoy ? MembresiaCongelamiento::ESTADO_ACTIVO : MembresiaCongelamiento::ESTADO_PROGRAMADO,
                'fkuser' => $adminId,
            ]);

            $membresia->update([
                'fecha_fin' => Carbon::parse($membresia->fecha_fin)->addDays($dias)->format('Y-m-d'),
            ]);

            $this->auditService->registrarCreacion('membresias', 'MembresiaCongelamiento', $congelamiento->id_congelamiento, $congelamiento->fresh()->toArray(), $adminId);

            return $congelamiento->fresh();
        });
    }

    /**
     * Finaliza anticipadamente: conserva solo los días realmente
     * congelados y retira la extensión no utilizada.
     */
    public function finalizarAnticipado(int $congelamientoId, int $adminId): MembresiaCongelamiento
    {
        return DB::transaction(function () use ($congelamientoId, $adminId) {
            $congelamiento = MembresiaCongelamiento::lockForUpdate()->findOrFail($congelamientoId);

            if (! in_array($congelamiento->estado, [MembresiaCongelamiento::ESTADO_PROGRAMADO, MembresiaCongelamiento::ESTADO_ACTIVO], true)) {
                throw ValidationException::withMessages(['congelamiento' => 'Solo se puede finalizar un congelamiento programado o activo.']);
            }

            $hoy = today()->format('Y-m-d');
            $membresia = $congelamiento->membresiaAlumno()->lockForUpdate()->firstOrFail();
            $anterior = $membresia->toArray();

            if ($hoy < $congelamiento->fecha_inicio->format('Y-m-d')) {
                // Aún no comenzó: equivale a cancelación total.
                $this->revertirExtension($membresia, $congelamiento->dias);
                $congelamiento->update(['estado' => MembresiaCongelamiento::ESTADO_CANCELADO]);
            } elseif ($hoy > $congelamiento->fecha_fin->format('Y-m-d')) {
                // Ya transcurrió completo: solo se marca el cierre.
                $congelamiento->update(['estado' => MembresiaCongelamiento::ESTADO_FINALIZADO]);
            } else {
                $usados = min($congelamiento->dias, Carbon::parse($congelamiento->fecha_inicio)->diffInDays(Carbon::parse($hoy)) + 1);
                $this->revertirExtension($membresia, $congelamiento->dias - $usados);
                $congelamiento->update([
                    'estado' => MembresiaCongelamiento::ESTADO_FINALIZADO,
                    'fecha_fin' => $hoy,
                    'dias' => $usados,
                ]);
            }

            $this->auditService->registrarEdicion('membresias', 'MembresiaCongelamiento', $congelamiento->id_congelamiento, ['estado' => 'vigente'], $congelamiento->fresh()->toArray(), $adminId);
            $this->auditService->registrarEdicion('membresias', 'MembresiaAlumno', $membresia->id_membresia_alumno, $anterior, $membresia->fresh()->toArray(), $adminId);

            return $congelamiento->fresh();
        });
    }

    /**
     * Cancela una programación antes de comenzar: revierte la extensión
     * completa y no consume la única oportunidad.
     */
    public function cancelarProgramado(int $congelamientoId, int $adminId): MembresiaCongelamiento
    {
        return DB::transaction(function () use ($congelamientoId, $adminId) {
            $congelamiento = MembresiaCongelamiento::lockForUpdate()->findOrFail($congelamientoId);

            if ($congelamiento->estado !== MembresiaCongelamiento::ESTADO_PROGRAMADO
                || today()->format('Y-m-d') >= $congelamiento->fecha_inicio->format('Y-m-d')) {
                throw ValidationException::withMessages(['congelamiento' => 'Solo se puede cancelar una programación antes de comenzar.']);
            }

            $membresia = $congelamiento->membresiaAlumno()->lockForUpdate()->firstOrFail();
            $anterior = $membresia->toArray();
            $this->revertirExtension($membresia, $congelamiento->dias);
            $congelamiento->update(['estado' => MembresiaCongelamiento::ESTADO_CANCELADO]);

            $this->auditService->registrarEdicion('membresias', 'MembresiaCongelamiento', $congelamiento->id_congelamiento, ['estado' => 'programado'], $congelamiento->fresh()->toArray(), $adminId);
            $this->auditService->registrarEdicion('membresias', 'MembresiaAlumno', $membresia->id_membresia_alumno, $anterior, $membresia->fresh()->toArray(), $adminId);

            return $congelamiento->fresh();
        });
    }

    /**
     * Ajusta directamente la fecha final de una membresía vigente.
     * No toca venta, deuda, precio ni comisión.
     */
    public function ajustarVencimiento(int $membresiaAlumnoId, string $nuevaFecha, string $motivo, int $adminId): MembresiaAlumno
    {
        return DB::transaction(function () use ($membresiaAlumnoId, $nuevaFecha, $motivo, $adminId) {
            $membresia = MembresiaAlumno::lockForUpdate()->findOrFail($membresiaAlumnoId);
            $hoy = today()->format('Y-m-d');

            if ($membresia->estado !== 'activa' || $membresia->fecha_fin < $hoy) {
                throw ValidationException::withMessages(['membresia' => 'Solo se puede ajustar una membresía vigente.']);
            }
            if ($nuevaFecha < $hoy || $nuevaFecha < $membresia->fecha_inicio) {
                throw ValidationException::withMessages(['fecha_nueva' => 'La nueva fecha no puede ser anterior a hoy ni a la fecha inicial.']);
            }
            if ($membresia->congelamientoVigente()) {
                throw ValidationException::withMessages(['membresia' => 'No se puede ajustar mientras exista un congelamiento programado o activo.']);
            }

            $anterior = $membresia->fecha_fin;
            $membresia->update(['fecha_fin' => $nuevaFecha]);

            MembresiaAjusteVigencia::create([
                'fkmembresia_alumno' => $membresia->id_membresia_alumno,
                'fecha_anterior' => $anterior,
                'fecha_nueva' => $nuevaFecha,
                'diferencia_dias' => Carbon::parse($anterior)->diffInDays(Carbon::parse($nuevaFecha), false),
                'motivo' => $motivo,
                'fkadmin' => $adminId,
            ]);

            $this->auditService->registrarEdicion('membresias', 'MembresiaAlumno', $membresia->id_membresia_alumno, ['fecha_fin' => $anterior], ['fecha_fin' => $nuevaFecha], $adminId);

            return $membresia->fresh();
        });
    }

    /**
     * Estado para la ficha: Activa, Congelamiento programado, Congelada o Vencida.
     */
    public function estadoParaFicha(MembresiaAlumno $membresia): string
    {
        $hoy = today()->format('Y-m-d');

        if ($membresia->estado === 'cancelada') {
            return 'Cancelada';
        }

        $congelado = $membresia->congelamientos()
            ->whereIn('estado', [MembresiaCongelamiento::ESTADO_PROGRAMADO, MembresiaCongelamiento::ESTADO_ACTIVO])
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->whereDate('fecha_fin', '>=', $hoy)
            ->exists();

        if ($congelado) {
            return 'Congelada';
        }

        $programado = $membresia->congelamientos()
            ->where('estado', MembresiaCongelamiento::ESTADO_PROGRAMADO)
            ->whereDate('fecha_inicio', '>', $hoy)
            ->exists();

        if ($programado) {
            return 'Congelamiento programado';
        }

        if ($membresia->estado !== 'activa' || $membresia->fecha_fin < $hoy) {
            return 'Vencida';
        }

        if (Carbon::parse($membresia->fecha_fin)->lte(now()->addDays(5)->endOfDay())) {
            return 'Por vencer';
        }

        return 'Activa';
    }

    public function congelamientoActivoDe(Alumno $alumno): ?MembresiaCongelamiento
    {
        $hoy = today()->format('Y-m-d');

        return MembresiaCongelamiento::whereHas('membresiaAlumno', fn ($q) => $q->where('fkalumno', $alumno->id_alumno))
            ->where('estado', MembresiaCongelamiento::ESTADO_ACTIVO)
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->whereDate('fecha_fin', '>=', $hoy)
            ->first();
    }

    public function puedeCongelar(MembresiaAlumno $membresia): bool
    {
        $hoy = today()->format('Y-m-d');

        return (bool) $membresia->membresia?->permite_congelamiento
            && $membresia->estado === 'activa'
            && $membresia->fecha_fin >= $hoy
            && ! $membresia->congelamientos()
                ->whereIn('estado', [
                    MembresiaCongelamiento::ESTADO_PROGRAMADO,
                    MembresiaCongelamiento::ESTADO_ACTIVO,
                    MembresiaCongelamiento::ESTADO_FINALIZADO,
                ])->exists();
    }

    public function puedeAjustar(MembresiaAlumno $membresia): bool
    {
        $hoy = today()->format('Y-m-d');

        return $membresia->estado === 'activa'
            && $membresia->fecha_fin >= $hoy
            && ! $membresia->congelamientoVigente();
    }

    private function revertirExtension(MembresiaAlumno $membresia, int $dias): void
    {
        if ($dias <= 0) {
            return;
        }

        $membresia->update([
            'fecha_fin' => Carbon::parse($membresia->fecha_fin)->subDays($dias)->format('Y-m-d'),
        ]);
    }
}
