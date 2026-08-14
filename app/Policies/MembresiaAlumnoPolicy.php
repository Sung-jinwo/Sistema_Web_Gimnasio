<?php

namespace App\Policies;

use App\Models\MembresiaAlumno;
use App\Models\User;

class MembresiaAlumnoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Local', 'Redes']);
    }

    public function view(User $user, MembresiaAlumno $membresiaAlumno): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        return $user->fksede === $membresiaAlumno->alumno->fksede;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Local', 'Redes']);
    }

    public function update(User $user, MembresiaAlumno $membresiaAlumno): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        return $user->fksede === $membresiaAlumno->alumno->fksede && $user->hasRole(['Local', 'Redes']);
    }

    public function delete(User $user, MembresiaAlumno $membresiaAlumno): bool
    {
        return $user->hasRole('Administrador');
    }
}
