<?php

namespace App\Policies;

use App\Models\Pago;
use App\Models\User;

class PagoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Local']);
    }

    public function view(User $user, Pago $pago): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        return $user->fksede === $pago->fksede;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Local']);
    }

    public function update(User $user, Pago $pago): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        return $user->fksede === $pago->fksede && $user->hasRole('Local');
    }

    public function delete(User $user, Pago $pago): bool
    {
        return $user->hasRole('Administrador');
    }
}
