<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Sede;
use App\Models\User;
use App\Services\FollowUpService;
use Illuminate\Http\Request;

class SeguimientoController extends Controller
{
    protected FollowUpService $followUpService;

    public function __construct(FollowUpService $followUpService)
    {
        $this->followUpService = $followUpService;
    }

    public function index(Request $request)
    {
        $this->authorizeSeguimiento();

        $filtros = [
            'sede' => $request->input('sede'),
            'empleado' => $request->input('empleado'),
            'mes' => $request->input('mes'),
            'anio' => $request->input('anio'),
            'dias' => $request->input('dias', 5),
        ];

        $tab = $request->input('tab', 'por_vencer');

        switch ($tab) {
            case 'vencidos':
                $registros = $this->followUpService->obtenerVencidos($filtros, auth()->user());
                break;
            case 'pagos_pendientes':
                $registros = $this->followUpService->obtenerPagosPendientes($filtros, auth()->user());
                break;
            default:
                $registros = $this->followUpService->obtenerVencimientos($filtros, auth()->user());
                break;
        }

        $sedes = Sede::where('sede_estado', true)->orderBy('sede_nombre')->get();
        $empleados = User::whereIn('fksede', auth()->user()->hasRole('Administrador') ? Sede::pluck('id_sede') : [auth()->user()->fksede])
            ->orderBy('name')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'registros' => $registros,
                'tab' => $tab,
            ]);
        }

        return view('seguimiento.index', compact('registros', 'sedes', 'empleados', 'tab', 'filtros'));
    }

    public function vencimientos(Request $request)
    {
        $request->merge(['tab' => 'por_vencer']);
        return $this->index($request);
    }

    public function vencidos(Request $request)
    {
        $request->merge(['tab' => 'vencidos']);
        return $this->index($request);
    }

    public function whatsapp($alumnoId, Request $request)
    {
        $this->authorizeSeguimiento();

        $alumno = Alumno::findOrFail($alumnoId);
        $tipo = $request->input('tipo', 'vencimiento');

        $datos = $this->followUpService->generarMensajeWhatsApp($alumno, $tipo);

        if ($request->expectsJson()) {
            return response()->json($datos);
        }

        return redirect($datos['url']);
    }

    protected function authorizeSeguimiento(): void
    {
        $user = auth()->user();
        if (!$user->hasRole(['Administrador', 'Local', 'Redes'])) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }
    }
}
