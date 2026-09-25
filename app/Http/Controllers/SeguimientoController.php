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

        $mes = $request->integer('mes');
        if ($mes < 1 || $mes > 12) {
            $mes = now()->month;
        }

        $anio = $request->integer('anio');
        if ($anio < now()->year - 1 || $anio > now()->year + 1) {
            $anio = now()->year;
        }

        $filtros = [
            'sede' => $request->input('sede'),
            'empleado' => $request->input('empleado'),
            'mes' => $mes,
            'anio' => $anio,
        ];

        $tab = $request->input('tab', 'por_vencer');
        if (! in_array($tab, ['por_vencer', 'vencidos'], true)) {
            $tab = 'por_vencer';
        }

        switch ($tab) {
            case 'vencidos':
                $registros = $this->followUpService->obtenerVencidos($filtros, auth()->user());
                break;
            default:
                $registros = $this->followUpService->obtenerVencimientos($filtros, auth()->user());
                break;
        }

        $sedes = Sede::where('sede_estado', true)->orderBy('sede_nombre')->get();
        $empleados = auth()->user()->hasRole('Administrador')
            ? User::whereIn('id', Alumno::query()->whereNotNull('fkuser')->select('fkuser'))->orderBy('name')->get()
            : collect();

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

        $alumno = Alumno::query()
            ->when(! auth()->user()->hasRole('Administrador'), function ($query) {
                $query->where('fksede', auth()->user()->fksede)
                    ->where('fkuser', auth()->id());
            })
            ->findOrFail($alumnoId);
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
        if (! $user->hasRole(['Administrador', 'Local', 'Redes'])) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }
    }
}
