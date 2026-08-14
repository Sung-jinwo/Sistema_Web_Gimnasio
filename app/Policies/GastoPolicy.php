<?php

namespace App\Policies;

use App\Models\Gasto;
use App\Models\User;

class GastoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Local']);
    }

    public function view(User $user, Gasto $gasto): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        return $user->fksede === $gasto->fksede;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Local']);
    }

    public function update(User $user, Gasto $gasto): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        if ($user->hasRole('Local') && $gasto->estado === 'pendiente') {
            return $user->fksede === $gasto->fksede;
        }

        return false;
    }

    public function delete(User $user, Gasto $gasto): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        if ($user->hasRole('Local') && $gasto->estado === 'pendiente') {
            return $user->fksede === $gasto->fksede;
        }

        return false;
    }

    public function aprobar(User $user, Gasto $gasto): bool
    {
        return $user->hasRole('Administrador') && $gasto->estado === 'pendiente';
    }

    public function rechazar(User $user, Gasto $gasto): bool
    {
        return $user->hasRole('Administrador') && $gasto->estado === 'pendiente';
    }
}
