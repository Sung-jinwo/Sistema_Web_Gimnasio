<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\MembresiaAlumno;
use App\Models\Venta;
use Carbon\Carbon;

class FollowUpService
{
    public function obtenerVencimientos(array $filtros = [], $usuario = null)
    {
        $query = MembresiaAlumno::with(['alumno.sede', 'membresia'])
            ->where('estado', 'activa');

        if ($usuario && !$usuario->hasRole('Administrador')) {
            $query->whereHas('alumno', function ($q) use ($usuario) {
                $q->where('fksede', $usuario->fksede);
            });
        }

        if (!empty($filtros['sede'])) {
            $query->whereHas('alumno', function ($q) use ($filtros) {
                $q->where('fksede', $filtros['sede']);
            });
        }

        if (!empty($filtros['empleado'])) {
            $query->whereHas('alumno', function ($q) use ($filtros) {
                $q->where('fkuser', $filtros['empleado']);
            });
        }

        $hoy = now()->format('Y-m-d');
        $query->where('fecha_fin', '>=', $hoy);

        if (!empty($filtros['mes'])) {
            $query->whereMonth('fecha_fin', $filtros['mes']);
            if (!empty($filtros['anio'])) {
                $query->whereYear('fecha_fin', $filtros['anio']);
            }
        }

        $dias = $filtros['dias'] ?? 5;
        $fechaLimite = now()->addDays($dias)->format('Y-m-d');
        $query->where('fecha_fin', '<=', $fechaLimite);

        return $query->orderBy('fecha_fin')->paginate(15);
    }

    public function obtenerVencidos(array $filtros = [], $usuario = null)
    {
        $query = MembresiaAlumno::with(['alumno.sede', 'membresia'])
            ->where('estado', 'activa');

        if ($usuario && !$usuario->hasRole('Administrador')) {
            $query->whereHas('alumno', function ($q) use ($usuario) {
                $q->where('fksede', $usuario->fksede);
            });
        }

        if (!empty($filtros['sede'])) {
            $query->whereHas('alumno', function ($q) use ($filtros) {
                $q->where('fksede', $filtros['sede']);
            });
        }

        if (!empty($filtros['empleado'])) {
            $query->whereHas('alumno', function ($q) use ($filtros) {
                $q->where('fkuser', $filtros['empleado']);
            });
        }

        $hoy = now()->format('Y-m-d');
        $query->where('fecha_fin', '<', $hoy);

        if (!empty($filtros['mes'])) {
            $query->whereMonth('fecha_fin', $filtros['mes']);
            if (!empty($filtros['anio'])) {
                $query->whereYear('fecha_fin', $filtros['anio']);
            }
        }

        return $query->orderBy('fecha_fin')->paginate(15);
    }

    public function obtenerPagosPendientes(array $filtros = [], $usuario = null)
    {
        $query = Venta::with(['alumno.sede', 'producto'])
            ->whereIn('estado_pago', ['parcial', 'pendiente']);

        if ($usuario && !$usuario->hasRole('Administrador')) {
            $query->where('fksede', $usuario->fksede);
        }

        if (!empty($filtros['sede'])) {
            $query->where('fksede', $filtros['sede']);
        }

        if (!empty($filtros['empleado'])) {
            $query->where('fkusers', $filtros['empleado']);
        }

        return $query->orderBy('created_at')->paginate(15);
    }

    public function generarMensajeWhatsApp(Alumno $alumno, string $tipo = 'vencimiento'): string
    {
        $nombre = $alumno->alum_nombre;
        $telefono = preg_replace('/[^0-9]/', '', $alumno->alum_telefo ?? '');

        if (str_starts_with($telefono, '0')) {
            $telefono = '51' . substr($telefono, 1);
        } elseif (!str_starts_with($telefono, '51')) {
            $telefono = '51' . $telefono;
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
            'pago_pendiente' => "Hola {$nombre} 👋\n\nTe recordamos que tienes un pago pendiente.\n\nPor favor acércate a regularizar tu situación. ¡Gracias!",
        ];

        return [
            'telefono' => $telefono,
            'mensaje' => $mensajes[$tipo] ?? $mensajes['vencimiento'],
            'url' => "https://wa.me/{$telefono}?text=" . urlencode($mensajes[$tipo] ?? $mensajes['vencimiento']),
        ];
    }
}
