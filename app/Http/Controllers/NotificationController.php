<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $userId = auth()->id();
        $soloNoLeidas = $request->input('solo_no_leidas', false);

        $notificaciones = $this->notificationService->obtenerNotificacionesUsuario($userId, $soloNoLeidas);
        $totalNoLeidas = $this->notificationService->contarNoLeidas($userId);

        if ($request->expectsJson()) {
            return response()->json([
                'notificaciones' => $notificaciones,
                'total_no_leidas' => $totalNoLeidas,
            ]);
        }

        return view('notificaciones.index', compact('notificaciones', 'totalNoLeidas'));
    }

    public function marcarLeida($id)
    {
        $userId = auth()->id();
        $this->notificationService->marcarComoLeida($id, $userId);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Notificación marcada como leída');
    }

    public function marcarTodasLeidas()
    {
        $userId = auth()->id();
        $this->notificationService->marcarTodasComoLeidas($userId);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Todas las notificaciones marcadas como leídas');
    }

    public function noLeidas()
    {
        $userId = auth()->id();
        $notificaciones = Notificacion::where('fkuser', $userId)
            ->noLeidas()
            ->noExpiradas()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $total = $this->notificationService->contarNoLeidas($userId);

        return response()->json([
            'notificaciones' => $notificaciones,
            'total' => $total,
        ]);
    }
}
