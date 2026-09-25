<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\MembresiaAlumno;
use App\Models\MembresiaCongelamiento;
use App\Models\Sede;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function buscarAlumno(string $codigoODocumento, ?string $tipoIngreso = null): ?Alumno
    {
        if ($tipoIngreso === 'dni') {
            return Alumno::where('alum_numDoc', $codigoODocumento)->first();
        }

        if ($tipoIngreso === 'codigo') {
            return Alumno::where('alum_codigo', $codigoODocumento)->first();
        }

        return Alumno::where('alum_numDoc', $codigoODocumento)
            ->orWhere('alum_codigo', $codigoODocumento)
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
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->where('fecha_fin', '>=', $hoy)
            ->exists();

        return $membresiaActiva;
    }

    /**
     * Congelamiento activo que bloquea el ingreso, con su fecha de fin.
     */
    public function congelamientoBloqueante(Alumno $alumno): ?MembresiaCongelamiento
    {
        return app(CongelamientoService::class)->congelamientoActivoDe($alumno);
    }

    public function registrarAsistencia(Alumno $alumno, int $sedeId, string $tipoIngreso = 'codigo', ?int $usuarioId = null): Asistencia
    {
        return DB::transaction(function () use ($alumno, $sedeId, $tipoIngreso, $usuarioId) {
            $asistencia = Asistencia::create([
                'fkalum' => $alumno->id_alumno,
                'fksede' => $sedeId,
                'fkuser' => $usuarioId,
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

        if ($congelado = $this->congelamientoBloqueante($alumno)) {
            return [
                'success' => false,
                'message' => 'Su membresía está congelada hasta el '.$congelado->fecha_fin->format('d/m/Y').'. No se permite el ingreso durante el congelamiento.',
                'tipo' => 'error',
            ];
        }

        $tipoIngreso = $alumno->alum_numDoc === $codigoODocumento ? 'dni' : 'codigo';
        $asistencia = $this->registrarAsistencia($alumno, $sedeId, $tipoIngreso);

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
