<?php

namespace App\Policies;

use App\Models\Comision;
use App\Models\User;

class ComisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Local']);
    }

    public function view(User $user, Comision $comision): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        return $user->id === $comision->fkuser;
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
