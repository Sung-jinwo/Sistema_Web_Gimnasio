<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venta;

class VentaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Local']);
    }

    public function view(User $user, Venta $venta): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        return $user->fksede === $venta->fksede;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Local']);
    }

    public function update(User $user, Venta $venta): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        return $user->fksede === $venta->fksede && $user->hasRole('Local');
    }

    public function delete(User $user, Venta $venta): bool
    {
        return $user->hasRole('Administrador');
    }
}
