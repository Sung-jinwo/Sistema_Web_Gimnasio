<?php

namespace App\Services;

use App\Models\Membresia;
use App\Models\MembresiaAlumno;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MembresiaService
{
    public function calcularFechaFin(Carbon $fechaInicio, int $duracion, string $modalidad, ?Carbon $fechaFinManual = null): Carbon
    {
        if ($modalidad === 'por_fechas' && $fechaFinManual) {
            return $fechaFinManual;
        }

        if ($duracion < 1) {
            throw new \InvalidArgumentException('La duración de la membresía debe ser al menos de un día.');
        }

        return $fechaInicio->copy()->addDays($duracion - 1);
    }

    public function asignarMembresia(
        int $alumnoId,
        int $membresiaId,
        string $modalidad,
        ?string $fechaInicio = null,
        ?string $fechaFin = null,
        ?int $ventaId = null
    ): MembresiaAlumno {
        if (! $ventaId) {
            throw new \LogicException('Toda membresía asignada debe estar vinculada a una venta.');
        }

        return DB::transaction(function () use ($alumnoId, $membresiaId, $modalidad, $fechaInicio, $fechaFin, $ventaId) {
            $membresia = Membresia::findOrFail($membresiaId);

            $tieneRangoFijo = $membresia->fecha_inicio_fija && $membresia->fecha_fin_fija;
            if ($tieneRangoFijo) {
                $fechaInicio = $membresia->fecha_inicio_fija?->format('Y-m-d');
                $fechaFin = $membresia->fecha_fin_fija?->format('Y-m-d');
                $modalidad = 'por_fechas';
            } else {
                $fechaFin = null;
            }

            $fechaInicioCarbon = $fechaInicio ? Carbon::parse($fechaInicio) : Carbon::now();
            $fechaFinCarbon = $fechaFin ? Carbon::parse($fechaFin) : null;

            $fechaFinCalculada = $this->calcularFechaFin(
                $fechaInicioCarbon,
                (int) ($membresia->mem_duracion ?? 0),
                $modalidad,
                $fechaFinCarbon
            );

            return MembresiaAlumno::create([
                'fkventa' => $ventaId,
                'fkalumno' => $alumnoId,
                'fkmem' => $membresiaId,
                'fecha_inicio' => $fechaInicioCarbon->format('Y-m-d'),
                'fecha_fin' => $fechaFinCalculada->format('Y-m-d'),
                'precio_vendido' => $membresia->mem_precio,
                'comision_aplicada' => $membresia->comision ?? 0,
                'modalidad' => $modalidad,
                'estado' => 'activa',
            ]);
        });
    }

    public function verificarVigencia(int $alumnoId): ?MembresiaAlumno
    {
        return MembresiaAlumno::where('fkalumno', $alumnoId)
            ->where('estado', 'activa')
            ->where('fecha_fin', '>=', now()->format('Y-m-d'))
            ->latest('fecha_inicio')
            ->first();
    }

    public function obtenerVencimientos(array $filtros = [])
    {
        $query = MembresiaAlumno::with(['alumno.sede', 'membresia'])
            ->where('estado', 'activa');

        if (! empty($filtros['sede'])) {
            $query->whereHas('alumno', function ($q) use ($filtros) {
                $q->where('fksede', $filtros['sede']);
            });
        }

        if (! empty($filtros['mes'])) {
            $query->whereMonth('fecha_fin', $filtros['mes'])
                ->whereYear('fecha_fin', $filtros['anio'] ?? now()->year);
        }

        if (! empty($filtros['estado'])) {
            $hoy = now()->format('Y-m-d');
            switch ($filtros['estado']) {
                case 'por_vencer':
                    $fechaLimite = now()->addDays(5)->format('Y-m-d');
                    $query->whereBetween('fecha_fin', [$hoy, $fechaLimite]);
                    break;
                case 'vencida':
                    $query->where('fecha_fin', '<', $hoy);
                    break;
                case 'activa':
                    $query->where('fecha_fin', '>=', $hoy);
                    break;
            }
        }

        return $query->orderBy('fecha_fin')->paginate(15);
    }

    public function actualizarEstadosVencidos(): int
    {
        $hoy = now()->format('Y-m-d');

        return MembresiaAlumno::where('estado', 'activa')
            ->where('fecha_fin', '<', $hoy)
            ->update(['estado' => 'vencida']);
    }
}
