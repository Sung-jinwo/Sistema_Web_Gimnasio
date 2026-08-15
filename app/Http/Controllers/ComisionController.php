<?php

namespace App\Http\Controllers;

use App\Models\Comision;
use App\Models\User;
use App\Services\CommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        if (! auth()->user()->hasRole('Administrador')) {
            $query->where('fkuser', auth()->id());
        }

        if ($request->has('estado') && $request->estado) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('tipo') && $request->tipo) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('empleado')) {
            $query->where('fkuser', $request->integer('empleado'));
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

        $empleados = User::whereHas('comisiones')->orderBy('name')->get(['id', 'name']);

        return view('comisiones.index', compact('comisiones', 'resumen', 'empleados'));
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

        if (! auth()->user()->hasRole('Administrador')) {
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

    public function liquidarSeleccion(Request $request)
    {
        abort_unless(auth()->user()->hasRole('Administrador'), 403);
        $ids = $request->validate(['comisiones' => 'required|array|min:1', 'comisiones.*' => 'integer|exists:comisiones,id_comision'])['comisiones'];
        DB::transaction(function () use ($ids) {
            $comisiones = Comision::whereIn('id_comision', $ids)->where('estado', 'pendiente')->lockForUpdate()->get();
            abort_if($comisiones->isEmpty() || $comisiones->pluck('fkuser')->unique()->count() !== 1, 422, 'Seleccione comisiones pendientes de un solo empleado.');
            $id = DB::table('liquidaciones_comision')->insertGetId(['fkuser' => $comisiones->first()->fkuser, 'liquidada_por' => auth()->id(), 'total' => $comisiones->sum('comision_final'), 'created_at' => now(), 'updated_at' => now()], 'id_liquidacion');
            DB::table('liquidacion_comision_detalles')->insert($comisiones->map(fn ($c) => ['fkliquidacion' => $id, 'fkcomision' => $c->id_comision])->all());
            Comision::whereIn('id_comision', $comisiones->pluck('id_comision'))->update(['estado' => 'liquidada', 'fecha_pago_real' => now()]);
        });

        return back()->with('success', 'Comisiones seleccionadas liquidadas correctamente.');
    }
}
