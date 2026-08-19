<?php

namespace App\Services;

use App\Models\MembresiaAlumno;
use App\Models\Notificacion;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;

class NotificationService
{
    public function generarNotificaciones(): array
    {
        $resultados = [
            'membresias_por_vencer' => 0,
            'membresias_vencidas' => 0,
            'pagos_pendientes' => 0,
            'pagos_vencidos' => 0,
        ];

        $resultados['membresias_por_vencer'] = $this->notificarMembresiasPorVencer();
        $resultados['membresias_vencidas'] = $this->notificarMembresiasVencidas();
        $resultados['pagos_pendientes'] = $this->notificarPagosPendientes();
        $resultados['pagos_vencidos'] = $this->notificarPagosVencidos();

        return $resultados;
    }

    protected function notificarMembresiasPorVencer(): int
    {
        $diasAntes = 3;
        $hoy = now()->format('Y-m-d');
        $fechaLimite = now()->addDays($diasAntes)->format('Y-m-d');

        $membresias = MembresiaAlumno::with('alumno')
            ->where('estado', 'activa')
            ->whereBetween('fecha_fin', [$hoy, $fechaLimite])
            ->get();

        $count = 0;
        foreach ($membresias as $membresia) {
            $usuarios = $this->obtenerUsuariosNotificables($membresia->alumno->fksede);

            foreach ($usuarios as $usuario) {
                if (! $this->existeNotificacion(
                    $usuario->id,
                    'membresia_por_vencer',
                    'membresia_alumno',
                    $membresia->id_membresia_alumno
                )) {
                    Notificacion::create([
                        'fkuser' => $usuario->id,
                        'tipo' => 'membresia_por_vencer',
                        'titulo' => 'Membresía próxima a vencer',
                        'mensaje' => "La membresía de {$membresia->alumno->nombreCompleto} vencerá el ".
                                     Carbon::parse($membresia->fecha_fin)->format('d/m/Y'),
                        'referencia_tipo' => 'membresia_alumno',
                        'referencia_id' => $membresia->id_membresia_alumno,
                        'fecha_expiracion' => now()->addDays(7),
                    ]);
                    $count++;
                }
            }
        }

        return $count;
    }

    protected function notificarMembresiasVencidas(): int
    {
        $hoy = now()->format('Y-m-d');

        $membresias = MembresiaAlumno::with('alumno')
            ->where('estado', 'activa')
            ->where('fecha_fin', '<', $hoy)
            ->get();

        $count = 0;
        foreach ($membresias as $membresia) {
            $usuarios = $this->obtenerUsuariosNotificables($membresia->alumno->fksede);

            foreach ($usuarios as $usuario) {
                if (! $this->existeNotificacion(
                    $usuario->id,
                    'membresia_vencida',
                    'membresia_alumno',
                    $membresia->id_membresia_alumno
                )) {
                    Notificacion::create([
                        'fkuser' => $usuario->id,
                        'tipo' => 'membresia_vencida',
                        'titulo' => 'Membresía vencida',
                        'mensaje' => "La membresía de {$membresia->alumno->nombreCompleto} venció el ".
                                     Carbon::parse($membresia->fecha_fin)->format('d/m/Y'),
                        'referencia_tipo' => 'membresia_alumno',
                        'referencia_id' => $membresia->id_membresia_alumno,
                        'fecha_expiracion' => now()->addDays(7),
                    ]);
                    $count++;
                }
            }
        }

        return $count;
    }

    protected function notificarPagosPendientes(): int
    {
        $ventas = Venta::with(['alumno', 'user'])
            ->whereIn('estado_pago', ['parcial', 'pendiente'])
            ->whereNull('fecha_acordada')
            ->get();

        $count = 0;
        foreach ($ventas as $venta) {
            if (! $venta->alumno) {
                continue;
            }

            $usuarios = $this->obtenerUsuariosNotificables($venta->fksede);

            foreach ($usuarios as $usuario) {
                if (! $this->existeNotificacion(
                    $usuario->id,
                    'pago_pendiente',
                    'venta',
                    $venta->id_venta
                )) {
                    Notificacion::create([
                        'fkuser' => $usuario->id,
                        'tipo' => 'pago_pendiente',
                        'titulo' => 'Pago pendiente',
                        'mensaje' => "{$venta->alumno->nombreCompleto} tiene un pago pendiente de S/ ".
                                     number_format($venta->saldo, 2),
                        'referencia_tipo' => 'venta',
                        'referencia_id' => $venta->id_venta,
                        'fecha_expiracion' => now()->addDays(7),
                    ]);
                    $count++;
                }
            }
        }

        return $count;
    }

    protected function notificarPagosVencidos(): int
    {
        $hoy = now()->format('Y-m-d');

        $ventas = Venta::with(['alumno', 'user'])
            ->whereIn('estado_pago', ['parcial', 'pendiente'])
            ->whereNotNull('fecha_acordada')
            ->where('fecha_acordada', '<', $hoy)
            ->get();

        $count = 0;
        foreach ($ventas as $venta) {
            if (! $venta->alumno) {
                continue;
            }

            $usuarios = $this->obtenerUsuariosNotificables($venta->fksede);

            foreach ($usuarios as $usuario) {
                if (! $this->existeNotificacion(
                    $usuario->id,
                    'pago_vencido',
                    'venta',
                    $venta->id_venta
                )) {
                    Notificacion::create([
                        'fkuser' => $usuario->id,
                        'tipo' => 'pago_vencido',
                        'titulo' => 'Pago vencido',
                        'mensaje' => "{$venta->alumno->nombreCompleto} tiene un pago vencido de S/ ".
                                     number_format($venta->saldo, 2).' (venció el '.
                                     Carbon::parse($venta->fecha_acordada)->format('d/m/Y').')',
                        'referencia_tipo' => 'venta',
                        'referencia_id' => $venta->id_venta,
                        'fecha_expiracion' => now()->addDays(7),
                    ]);
                    $count++;
                }
            }
        }

        return $count;
    }

    public function expirarNotificaciones(): int
    {
        return Notificacion::whereNotNull('fecha_expiracion')
            ->where('fecha_expiracion', '<', now())
            ->delete();
    }

    public function marcarComoLeida(int $notificacionId, int $userId): bool
    {
        $notificacion = Notificacion::where('id_notificacion', $notificacionId)
            ->where('fkuser', $userId)
            ->first();

        if ($notificacion) {
            $notificacion->update(['leida' => true]);

            return true;
        }

        return false;
    }

    public function marcarTodasComoLeidas(int $userId): int
    {
        return Notificacion::where('fkuser', $userId)
            ->where('leida', false)
            ->update(['leida' => true]);
    }

    public function obtenerNotificacionesUsuario(int $userId, bool $soloNoLeidas = false)
    {
        $query = Notificacion::where('fkuser', $userId)
            ->noExpiradas()
            ->orderByDesc('created_at');

        if ($soloNoLeidas) {
            $query->noLeidas();
        }

        return $query->paginate(20);
    }

    public function contarNoLeidas(int $userId): int
    {
        return Notificacion::where('fkuser', $userId)
            ->noLeidas()
            ->noExpiradas()
            ->count();
    }

    protected function obtenerUsuariosNotificables(int $sedeId)
    {
        return User::where('fksede', $sedeId)
            ->whereIn('rol', [User::ROL_ADMIN, User::ROL_EMPLEDO_LOCAL])
            ->where('estado', true)
            ->get();
    }

    protected function existeNotificacion(int $userId, string $tipo, string $referenciaTipo, int $referenciaId): bool
    {
        return Notificacion::where('fkuser', $userId)
            ->where('tipo', $tipo)
            ->where('referencia_tipo', $referenciaTipo)
            ->where('referencia_id', $referenciaId)
            ->noExpiradas()
            ->exists();
    }
}
