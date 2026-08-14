<?php

namespace App\Http\Controllers;

use App\Models\Comision;
use App\Services\CommissionService;
use Illuminate\Http\Request;

class ComisionController extends Controller
{
    protected CommissionService $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Comision::class);

        $query = Comision::with(['usuario', 'venta.alumno', 'venta.producto']);

        if (!auth()->user()->hasRole('Administrador')) {
            $query->where('fkuser', auth()->id());
        }

        if ($request->has('estado') && $request->estado) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('tipo') && $request->tipo) {
            $query->where('tipo', $request->tipo);
        }

        $comisiones = $query->orderByDesc('created_at')->paginate(15);

        $resumen = [
            'total_base' => $comisiones->sum('comision_base'),
            'total_penalizaciones' => $comisiones->sum('penalizacion'),
            'total_final' => $comisiones->sum('comision_final'),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'comisiones' => $comisiones,
                'resumen' => $resumen,
            ]);
        }

        return view('comisiones.index', compact('comisiones', 'resumen'));
    }

    public function misComisiones(Request $request)
    {
        $query = Comision::with(['venta.alumno', 'venta.producto'])
            ->where('fkuser', auth()->id());

        if ($request->has('estado') && $request->estado) {
            $query->where('estado', $request->estado);
        }

        $comisiones = $query->orderByDesc('created_at')->paginate(15);

        $resumen = [
            'total_base' => $comisiones->sum('comision_base'),
            'total_penalizaciones' => $comisiones->sum('penalizacion'),
            'total_final' => $comisiones->sum('comision_final'),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'comisiones' => $comisiones,
                'resumen' => $resumen,
            ]);
        }

        return view('comisiones.mis_comisiones', compact('comisiones', 'resumen'));
    }

    public function show($id)
    {
        $comision = Comision::with(['usuario', 'venta.alumno', 'venta.producto'])->findOrFail($id);
        $this->authorize('view', $comision);

        $calculo = $this->commissionService->calcularComisionFinal($id);

        if (request()->expectsJson()) {
            return response()->json([
                'comision' => $comision,
                'calculo' => $calculo,
            ]);
        }

        return view('comisiones.show', compact('comision', 'calculo'));
    }

    public function liquidar(Request $request, $id)
    {
        $comision = Comision::findOrFail($id);

        if (!auth()->user()->hasRole('Administrador')) {
            abort(403, 'No autorizado');
        }

        $comisionActualizada = $this->commissionService->registrarPagoComision($id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Comisión liquidada exitosamente',
                'comision' => $comisionActualizada,
            ]);
        }

        return redirect()->route('comisiones.index')
            ->with('success', 'Comisión liquidada exitosamente');
    }
}
