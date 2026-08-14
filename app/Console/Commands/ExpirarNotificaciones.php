<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class ExpirarNotificaciones extends Command
{
    protected $signature = 'notificaciones:expirar';

    protected $description = 'Elimina notificaciones expiradas';

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle(): int
    {
        $this->info('Expirando notificaciones...');

        $eliminadas = $this->notificationService->expirarNotificaciones();

        $this->info("{$eliminadas} notificaciones expiradas eliminadas");

        return Command::SUCCESS;
    }
}
