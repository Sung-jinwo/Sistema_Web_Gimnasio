<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', \App\Models\AuditLog::class);

        $filtros = $request->only(['usuario', 'modulo', 'accion', 'fecha_inicio', 'fecha_fin']);
        $logs = $this->auditService->obtenerLogs($filtros);
        $usuarios = User::orderBy('name')->get();

        $modulos = [
            'alumnos' => 'Alumnos',
            'ventas' => 'Ventas',
            'pagos' => 'Pagos',
            'gastos' => 'Gastos',
            'caja' => 'Caja',
            'usuarios' => 'Usuarios',
            'membresias' => 'Membresías',
            'productos' => 'Productos',
        ];

        $acciones = [
            'crear' => 'Creación',
            'editar' => 'Edición',
            'eliminar' => 'Eliminación',
            'aprobar' => 'Aprobación',
            'rechazar' => 'Rechazo',
        ];

        if ($request->expectsJson()) {
            return response()->json($logs);
        }

        return view('auditoria.index', compact('logs', 'usuarios', 'modulos', 'acciones', 'filtros'));
    }

    public function show($id)
    {
        $log = \App\Models\AuditLog::with('usuario')->findOrFail($id);
        $this->authorize('view', $log);

        if (request()->expectsJson()) {
            return response()->json($log);
        }

        return view('auditoria.show', compact('log'));
    }
}
