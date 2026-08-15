<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class GenerarNotificaciones extends Command
{
    protected $signature = 'notificaciones:generar';

    protected $description = 'Genera notificaciones de membresías por vencer, vencidas y pagos pendientes';

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle(): int
    {
        $this->info('Generando notificaciones...');

        $resultados = $this->notificationService->generarNotificaciones();

        $this->info('Membresías por vencer: '.$resultados['membresias_por_vencer'].' notificaciones');
        $this->info('Membresías vencidas: '.$resultados['membresias_vencidas'].' notificaciones');
        $this->info('Pagos pendientes: '.$resultados['pagos_pendientes'].' notificaciones');
        $this->info('Pagos vencidos: '.$resultados['pagos_vencidos'].' notificaciones');

        $total = array_sum($resultados);
        $this->info("Total: {$total} notificaciones generadas");

        return Command::SUCCESS;
    }
}
