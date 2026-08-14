<?php

namespace App\Policies;

use App\Models\User;

class UsuarioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Administrador');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole('Administrador');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Administrador');
    }

    public function update(User $user, User $model): bool
    {
        if (! $user->hasRole('Administrador')) {
            return false;
        }

        return true;
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->hasRole('Administrador')) {
            return false;
        }

        if ($user->id === $model->id) {
            return false;
        }

        return true;
    }

    public function toggleEstado(User $user, User $model): bool
    {
        if (! $user->hasRole('Administrador')) {
            return false;
        }

        if ($user->id === $model->id) {
            return false;
        }

        return true;
    }
}
