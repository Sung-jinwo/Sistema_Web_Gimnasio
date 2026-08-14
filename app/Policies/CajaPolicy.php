<?php

namespace App\Policies;

use App\Models\Caja;
use App\Models\User;

class CajaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Local']);
    }

    public function view(User $user, Caja $caja): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        return $user->fksede === $caja->fksede;
    }

    public function abrir(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Local']);
    }

    public function cerrar(User $user, Caja $caja): bool
    {
        if (!$user->hasRole(['Administrador', 'Local'])) {
            return false;
        }

        if ($user->hasRole('Local') && $user->fksede !== $caja->fksede) {
            return false;
        }

        return $caja->estado === 'abierta';
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

        return $user->fksede === $caja->fksede && $caja->estado === 'cerrada';
    }
}
