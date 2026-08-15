<?php

namespace App\Services;

use App\Models\Alumno;

class AlumnoService
{
    public function buscarPorDni(string $dni): ?Alumno
    {
        return Alumno::where('alum_numDoc', $dni)->first();
    }

    public function buscarPorCodigo(string $codigo): ?Alumno
    {
        return Alumno::where('alum_codigo', $codigo)->first();
    }

    public function validarDniUnico(string $dni, ?int $id = null): bool
    {
        $query = Alumno::where('alum_numDoc', $dni);

        if ($id) {
            $query->where('id_alumno', '!=', $id);
        }

        return ! $query->exists();
    }

    public function validarCodigoUnico(string $codigo, ?int $id = null): bool
    {
        $query = Alumno::where('alum_codigo', $codigo);

        if ($id) {
            $query->where('id_alumno', '!=', $id);
        }

        return ! $query->exists();
    }

    public function obtenerFichaCompleta(int $alumnoId): array
    {
        $alumno = Alumno::with([
            'sede',
            'pagos.membresia',
            'pagos.metodo',
            'asistencias',
            'membresiasAlumno.membresia',
        ])->findOrFail($alumnoId);

        $membresias = $alumno->membresiasAlumno->map(function ($membresiaAlumno) {
            return [
                'plan' => $membresiaAlumno->membresia->mem_nombre ?? 'N/A',
                'inicio' => \Carbon\Carbon::parse($membresiaAlumno->fecha_inicio)->format('d/m/Y'),
                'vencimiento' => \Carbon\Carbon::parse($membresiaAlumno->fecha_fin)->format('d/m/Y'),
                'estado' => $membresiaAlumno->estado_formato,
                'monto' => $membresiaAlumno->precio_vendido,
                'modalidad' => $membresiaAlumno->modalidad,
            ];
        });

        $pagos = $alumno->pagos->map(function ($pago) {
            return [
                'fecha' => $pago->created_at,
                'concepto' => $pago->membresia->mem_nombre ?? 'Pago',
                'total' => $pago->pag_monto,
                'pagado' => $pago->pag_monto,
                'saldo' => 0,
                'estado' => $pago->estado_pago,
                'metodo' => $pago->metodo->metod_nombre ?? 'N/A',
            ];
        });

        $asistencias = $alumno->asistencias->map(function ($asistencia) {
            return [
                'fecha' => $asistencia->visi_fecha->format('d/m/Y'),
                'hora' => $asistencia->visi_fecha->format('H:i'),
                'sede' => $asistencia->sede->sede_nombre ?? 'N/A',
            ];
        });

        return [
            'alumno' => $alumno,
            'membresias' => $membresias,
            'pagos' => $pagos,
            'asistencias' => $asistencias,
        ];
    }

    public function scopePorSede($query, ?int $sedeId, $usuario)
    {
        if ($usuario->hasRole('Administrador')) {
            return $query;
        }

        return $query->where('fksede', $sedeId);
    }

    public function obtenerAlumnosConFiltros(array $filtros, $usuario): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Alumno::with(['sede']);

        $query = $this->scopePorSede($query, $usuario->fksede, $usuario);

        if (! empty($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function ($q) use ($search) {
                $q->where('alum_nombre', 'like', "%{$search}%")
                    ->orWhere('alum_apellido', 'like', "%{$search}%")
                    ->orWhere('alum_numDoc', 'like', "%{$search}%")
                    ->orWhere('alum_codigo', 'like', "%{$search}%");
            });
        }

        if (! empty($filtros['sede'])) {
            $query->where('fksede', $filtros['sede']);
        }

        if (! empty($filtros['estado'])) {
            $query->where('alum_estado', $filtros['estado']);
        }

        return $query->orderBy('alum_apellido')->orderBy('alum_nombre')->paginate(15);
    }
}
