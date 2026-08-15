<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\MembresiaAlumno;
use App\Models\Sede;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function buscarAlumno(string $codigoODocumento): ?Alumno
    {
        return Alumno::where('alum_codigo', $codigoODocumento)
            ->orWhere('alum_numDoc', $codigoODocumento)
            ->first();
    }

    public function validarAlumnoActivo(Alumno $alumno): bool
    {
        return $alumno->alum_estado == true;
    }

    public function validarMembresiaVigente(Alumno $alumno): bool
    {
        $hoy = Carbon::now()->format('Y-m-d');

        $membresiaActiva = MembresiaAlumno::where('fkalumno', $alumno->id_alumno)
            ->where('estado', 'activa')
            ->where('fecha_fin', '>=', $hoy)
            ->exists();

        return $membresiaActiva;
    }

    public function validarDuplicadoHoy(Alumno $alumno, int $sedeId): bool
    {
        $hoy = Carbon::now()->format('Y-m-d');

        $existeHoy = Asistencia::where('fkalum', $alumno->id_alumno)
            ->where('fksede', $sedeId)
            ->whereDate('visi_fecha', $hoy)
            ->exists();

        return $existeHoy;
    }

    public function registrarAsistencia(Alumno $alumno, int $sedeId, string $tipoIngreso = 'codigo'): Asistencia
    {
        return DB::transaction(function () use ($alumno, $sedeId, $tipoIngreso) {
            $asistencia = Asistencia::create([
                'fkalum' => $alumno->id_alumno,
                'fksede' => $sedeId,
                'fkuser' => null,
                'visi_fecha' => now(),
                'tipo_ingreso' => $tipoIngreso,
            ]);

            return $asistencia->load('alumno');
        });
    }

    public function procesarRegistroPublico(string $codigoODocumento, int $sedeId): array
    {
        $alumno = $this->buscarAlumno($codigoODocumento);

        if (! $alumno) {
            return [
                'success' => false,
                'message' => 'Alumno no encontrado',
                'tipo' => 'error',
            ];
        }

        if (! $this->validarAlumnoActivo($alumno)) {
            return [
                'success' => false,
                'message' => 'El alumno está inactivo',
                'tipo' => 'error',
            ];
        }

        if (! $this->validarMembresiaVigente($alumno)) {
            return [
                'success' => false,
                'message' => 'La membresía del alumno está vencida',
                'tipo' => 'error',
            ];
        }

        if ($this->validarDuplicadoHoy($alumno, $sedeId)) {
            return [
                'success' => false,
                'message' => 'El alumno ya registró asistencia hoy',
                'tipo' => 'warning',
            ];
        }

        $asistencia = $this->registrarAsistencia($alumno, $sedeId, 'codigo');

        return [
            'success' => true,
            'message' => 'Asistencia registrada exitosamente',
            'alumno' => $alumno,
            'asistencia' => $asistencia,
            'tipo' => 'success',
        ];
    }

    public function obtenerSedesActivas()
    {
        return Sede::where('sede_estado', true)
            ->orderBy('sede_nombre')
            ->get(['id_sede', 'sede_nombre']);
    }
}
