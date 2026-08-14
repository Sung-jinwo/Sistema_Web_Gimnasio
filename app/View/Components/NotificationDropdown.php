<?php

namespace App\View\Components;

use App\Services\NotificationService;
use Illuminate\View\Component;

class NotificationDropdown extends Component
{
    public int $totalNoLeidas;
    public $notificaciones;

    public function __construct()
    {
        $notificationService = app(NotificationService::class);
        $this->totalNoLeidas = $notificationService->contarNoLeidas(auth()->id());
        $this->notificaciones = \App\Models\Notificacion::where('fkuser', auth()->id())
            ->noLeidas()
            ->noExpiradas()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('components.notification-dropdown');
    }
}
