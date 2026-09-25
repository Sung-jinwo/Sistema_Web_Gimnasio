<?php

namespace App\Policies;

use App\Models\Caja;
use App\Models\User;

class CajaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Local', 'Redes']);
    }

    public function view(User $user, Caja $caja): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        return $user->id === $caja->fkuser;
    }

    public function abrir(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Local', 'Redes']);
    }

    public function cerrar(User $user, Caja $caja): bool
    {
        if (! $user->hasRole(['Administrador', 'Local', 'Redes'])) {
            return false;
        }

        if (! in_array($caja->estado, ['abierta', 'pendiente_cierre', 'observada'], true)) {
            return false;
        }

        // El empleado solo cierra su propia caja; el Administrador puede cerrar en representación.
        if (! $user->hasRole('Administrador') && (int) $caja->fkuser !== (int) $user->id) {
            return false;
        }

        return true;
    }

    public function aprobar(User $user, Caja $caja): bool
    {
        return $user->hasRole('Administrador') && $caja->estado === 'pendiente_revision';
    }

    public function observar(User $user, Caja $caja): bool
    {
        return $this->aprobar($user, $caja);
    }

    public function anular(User $user, Caja $caja): bool
    {
        return $user->hasRole('Administrador') && $caja->estado === 'cerrada';
    }

    public function verPdf(User $user, Caja $caja): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        return (int) $caja->fkuser === (int) $user->id && $caja->estado === 'cerrada';
    }
}
