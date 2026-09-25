<?php

namespace App\Policies;

use App\Models\Producto;
use App\Models\User;

class ProductoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('productos.ver');
    }

    public function view(User $user, Producto $producto): bool
    {
        if (! $user->can('productos.ver')) {
            return false;
        }

        return $user->hasRole('Administrador') || $user->fksede === $producto->fksede;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Administrador') && $user->can('productos.crear');
    }

    public function update(User $user, Producto $producto): bool
    {
        return $user->hasRole('Administrador') && $user->can('productos.editar');
    }

    public function delete(User $user, Producto $producto): bool
    {
        return $this->update($user, $producto);
    }
}
