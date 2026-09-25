<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\MembresiaAlumno;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class FollowUpService
{
    public function obtenerVencimientos(array $filtros = [], $usuario = null)
    {
        $query = MembresiaAlumno::with(['alumno.sede', 'membresia'])
            ->where('estado', 'activa');

        $this->aplicarAlcance($query, $filtros, $usuario);

        $hoy = now()->format('Y-m-d');
        $mes = $filtros['mes'] ?? now()->month;
        $anio = $filtros['anio'] ?? now()->year;
        $query->where('fecha_fin', '>=', $hoy)
            ->whereMonth('fecha_fin', $mes)
            ->whereYear('fecha_fin', $anio);

        return $query->orderBy('fecha_fin')->paginate(15);
    }

    public function obtenerVencidos(array $filtros = [], $usuario = null)
    {
        $query = MembresiaAlumno::with(['alumno.sede', 'membresia'])
            ->where('estado', 'activa');

        $this->aplicarAlcance($query, $filtros, $usuario);

        $hoy = now()->format('Y-m-d');
        $mes = $filtros['mes'] ?? now()->month;
        $anio = $filtros['anio'] ?? now()->year;
        $query->where('fecha_fin', '<', $hoy)
            ->whereMonth('fecha_fin', $mes)
            ->whereYear('fecha_fin', $anio);

        return $query->orderBy('fecha_fin')->paginate(15);
    }

    public function generarMensajeWhatsApp(Alumno $alumno, string $tipo = 'vencimiento'): array
    {
        $nombre = $alumno->alum_nombre;
        $telefono = preg_replace('/[^0-9]/', '', $alumno->alum_telefo ?? '');

        if (str_starts_with($telefono, '0')) {
            $telefono = '51'.substr($telefono, 1);
        } elseif (! str_starts_with($telefono, '51')) {
            $telefono = '51'.$telefono;
        }

        $membresiaActiva = MembresiaAlumno::where('fkalumno', $alumno->id_alumno)
            ->where('estado', 'activa')
            ->latest('fecha_inicio')
            ->first();

        $fechaVencimiento = $membresiaActiva
            ? Carbon::parse($membresiaActiva->fecha_fin)->format('d/m/Y')
            : 'próxima';

        $mensajes = [
            'vencimiento' => "Hola {$nombre} 👋\n\nTe recordamos que tu membresía está próxima a vencer el {$fechaVencimiento}.\n\nSi deseas renovarla, podemos ayudarte. ¡Te esperamos!",
            'vencido' => "Hola {$nombre} 👋\n\nTu membresía venció el {$fechaVencimiento}.\n\nTe invitamos a renovarla para seguir disfrutando de nuestros servicios. ¡Te esperamos!",
        ];

        return [
            'telefono' => $telefono,
            'mensaje' => $mensajes[$tipo] ?? $mensajes['vencimiento'],
            'url' => "https://wa.me/{$telefono}?text=".urlencode($mensajes[$tipo] ?? $mensajes['vencimiento']),
        ];
    }

    private function aplicarAlcance(Builder $query, array $filtros, $usuario): void
    {
        if ($usuario && ! $usuario->hasRole('Administrador')) {
            $query->whereHas('alumno', function ($alumnos) use ($usuario) {
                $alumnos->where('fksede', $usuario->fksede)
                    ->where('fkuser', $usuario->id);
            });

            return;
        }

        if (! empty($filtros['sede'])) {
            $query->whereHas('alumno', fn ($alumnos) => $alumnos->where('fksede', $filtros['sede']));
        }

        if (! empty($filtros['empleado'])) {
            $query->whereHas('alumno', fn ($alumnos) => $alumnos->where('fkuser', $filtros['empleado']));
        }
    }
}
