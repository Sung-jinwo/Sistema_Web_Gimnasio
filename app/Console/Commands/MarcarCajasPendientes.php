<?php

namespace App\Console\Commands;

use App\Services\CashClosingService;
use Illuminate\Console\Command;

class MarcarCajasPendientes extends Command
{
    protected $signature = 'cajas:marcar-pendientes';

    protected $description = 'Marca como pendientes de cierre las cajas de días operativos anteriores';

    public function handle(CashClosingService $service): int
    {
        $cantidad = $service->marcarVencidas();
        $this->info("Cajas marcadas como pendientes: {$cantidad}");

        return self::SUCCESS;
    }
}
