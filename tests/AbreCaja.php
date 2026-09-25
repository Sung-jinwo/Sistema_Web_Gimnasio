<?php

namespace Tests;

use App\Models\Caja;
use App\Models\User;

trait AbreCaja
{
    protected function abrirCajaPara(User $user, ?int $sedeId = null): Caja
    {
        return Caja::create([
            'fksede' => $sedeId ?? $user->fksede,
            'fkuser' => $user->id,
            'fecha_apertura' => now(),
            'fecha_operativa' => today(),
            'monto_inicial' => 0,
            'estado' => 'abierta',
        ]);
    }
}
