<?php

namespace App\Policies;

use App\Models\Alumno;
use App\Models\User;

class AlumnoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Local', 'Redes', 'Asistencia']);
    }

    public function view(User $user, Alumno $alumno): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        return $user->fksede === $alumno->fksede;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Local', 'Redes']);
    }

    public function update(User $user, Alumno $alumno): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        return $user->fksede === $alumno->fksede && $user->hasRole(['Local', 'Redes']);
    }

    public function delete(User $user, Alumno $alumno): bool
    {
        if (!$user->hasRole('Administrador')) {
            return false;
        }

        return true;
    }

    public function restore(User $user, Alumno $alumno): bool
    {
        return $user->hasRole('Administrador');
    }

    public function forceDelete(User $user, Alumno $alumno): bool
    {
        return $user->hasRole('Administrador');
    }
}
