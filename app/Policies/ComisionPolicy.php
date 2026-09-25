<?php

namespace App\Policies;

use App\Models\Comision;
use App\Models\User;

class ComisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Administrador');
    }

    public function view(User $user, Comision $comision): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        return $user->hasRole(['Local', 'Redes']) && (int) $comision->fkuser === (int) $user->id;
    }

    public function aprobar(User $user): bool
    {
        return $user->hasRole('Administrador');
    }

    public function liquidar(User $user): bool
    {
        return $user->hasRole('Administrador');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Comision $comision): bool
    {
        return false;
    }

    public function delete(User $user, Comision $comision): bool
    {
        return false;
    }
}
