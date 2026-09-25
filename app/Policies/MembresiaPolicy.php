<?php

namespace App\Policies;

use App\Models\Membresia;
use App\Models\User;

class MembresiaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('membresias.ver');
    }

    public function view(User $user, Membresia $membresia): bool
    {
        return $user->can('membresias.ver');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Administrador') && $user->can('membresias.crear');
    }

    public function update(User $user, Membresia $membresia): bool
    {
        return $user->hasRole('Administrador') && $user->can('membresias.editar');
    }

    public function delete(User $user, Membresia $membresia): bool
    {
        return $this->update($user, $membresia);
    }
}
