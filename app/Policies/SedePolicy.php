<?php

namespace App\Policies;

use App\Models\Sede;
use App\Models\User;

class SedePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Local']);
    }

    public function view(User $user, Sede $sede): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        return $user->fksede === $sede->id_sede;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Administrador');
    }

    public function update(User $user, Sede $sede): bool
    {
        return $user->hasRole('Administrador');
    }

    public function delete(User $user, Sede $sede): bool
    {
        return $user->hasRole('Administrador');
    }

    public function toggleEstado(User $user, Sede $sede): bool
    {
        return $user->hasRole('Administrador');
    }
}
