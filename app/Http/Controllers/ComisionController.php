<?php

namespace App\Http\Controllers;

use App\Models\Comision;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComisionController extends Controller
{
    protected CommissionService $commissionService;

    protected AuditService $auditService;

    public function __construct(CommissionService $commissionService, AuditService $auditService)
    {
        $this->commissionService = $commissionService;
        $this->auditService = $auditService;
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
        $metodosPago = \App\Models\MetodoPago::orderBy('metod_nombre')->get(['id_metod', 'metod_nombre']);

        return view('comisiones.index', compact('comisiones', 'resumen', 'empleados', 'metodosPago'));
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
            'esperando_cobro' => (clone $query)->where('estado', Comision::ESTADO_ESPERANDO_PAGO)->sum('comision_base'),
            'pendientes_revision' => (clone $query)->where('estado', Comision::ESTADO_PENDIENTE_REVISION)->sum('comision_final'),
            'aprobadas_por_cobrar' => (clone $query)->where('estado', Comision::ESTADO_APROBADA)->sum('comision_final'),
            'pagadas' => (clone $query)->where('estado', Comision::ESTADO_LIQUIDADA)->sum('comision_final'),
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
        $comision = Comision::with(['usuario', 'aprobadaPor', 'venta.alumno', 'venta.producto'])->findOrFail($id);
        $this->authorize('view', $comision);

        $calculo = $this->commissionService->calcularComisionFinal($id);
        $metodosPago = auth()->user()->hasRole('Administrador')
            ? \App\Models\MetodoPago::orderBy('metod_nombre')->get(['id_metod', 'metod_nombre'])
            : collect();

        if (request()->expectsJson()) {
            return response()->json([
                'comision' => $comision,
                'calculo' => $calculo,
            ]);
        }

        return view('comisiones.show', compact('comision', 'calculo', 'metodosPago'));
    }

    public function aprobar(Request $request, $id)
    {
        $this->authorize('aprobar', Comision::class);

        try {
            $comision = $this->commissionService->aprobarComision((int) $id, auth()->id());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError($request, $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Comisión aprobada. Queda pendiente de liquidación.',
                'comision' => $comision,
            ]);
        }

        return back()->with('success', 'Comisión aprobada. Queda pendiente de liquidación.');
    }

    public function observar(Request $request, $id)
    {
        $this->authorize('aprobar', Comision::class);

        $data = $request->validate([
            'motivo_observacion' => 'required|string|max:1000',
        ], [
            'motivo_observacion.required' => 'Debe indicar el motivo de la observación.',
        ]);

        try {
            $comision = $this->commissionService->observarComision((int) $id, auth()->id(), $data['motivo_observacion']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError($request, $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Comisión observada.',
                'comision' => $comision,
            ]);
        }

        return back()->with('success', 'Comisión observada.');
    }

    /**
     * Aprueba en lote solo las verificadas. Cada comisión conserva su
     * responsable y su auditoría individual.
     */
    public function aprobarSeleccion(Request $request)
    {
        $this->authorize('aprobar', Comision::class);

        $ids = $request->validate([
            'comisiones' => 'required|array|min:1',
            'comisiones.*' => 'integer|exists:comisiones,id_comision',
        ])['comisiones'];

        $aprobadas = 0;
        $errores = [];
        foreach ($ids as $id) {
            try {
                $this->commissionService->aprobarComision((int) $id, auth()->id());
                $aprobadas++;
            } catch (\Illuminate\Validation\ValidationException $e) {
                $errores[] = "#{$id}: {$e->getMessage()}";
            }
        }

        $mensaje = "Se aprobaron {$aprobadas} de ".count($ids).' comisiones.';
        if ($errores !== []) {
            $mensaje .= ' No aprobadas: '.implode(' | ', $errores);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => $aprobadas > 0, 'message' => $mensaje], $aprobadas > 0 ? 200 : 422);
        }

        return back()->with($aprobadas > 0 ? 'success' : 'error', $mensaje);
    }

    public function liquidar(Request $request, $id)
    {
        $this->authorize('liquidar', Comision::class);

        $data = $request->validate([
            'fkmetodo' => 'required|exists:metodos_pago,id_metod',
            'referencia' => 'nullable|string|max:100',
            'observacion' => 'nullable|string|max:1000',
        ], [
            'fkmetodo.required' => 'Debe seleccionar el método de pago.',
            'fkmetodo.exists' => 'El método de pago no es válido.',
        ]);

        try {
            $comisionActualizada = $this->commissionService->registrarPagoComision((int) $id, $data + ['liquidada_por' => auth()->id()]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError($request, $e->getMessage());
        }

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
        $this->authorize('liquidar', Comision::class);

        $data = $request->validate([
            'comisiones' => 'required|array|min:1',
            'comisiones.*' => 'integer|exists:comisiones,id_comision',
            'fkmetodo' => 'required|exists:metodos_pago,id_metod',
            'referencia' => 'nullable|string|max:100',
            'observacion' => 'nullable|string|max:1000',
        ], [
            'fkmetodo.required' => 'Debe seleccionar el método de pago.',
        ]);

        try {
            DB::transaction(function () use ($data) {
                $comisiones = Comision::with('venta')->whereIn('id_comision', $data['comisiones'])
                    ->where('estado', Comision::ESTADO_APROBADA)->lockForUpdate()->get();
                if ($comisiones->count() !== count($data['comisiones']) || $comisiones->pluck('fkuser')->unique()->count() !== 1) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['error' => 'Seleccione únicamente comisiones aprobadas de un solo empleado.']);
                }
                if ($comisiones->contains(fn ($comision) => $comision->venta && (float) $comision->venta->saldo > 0)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['error' => 'No se pueden liquidar comisiones de ventas con saldo pendiente.']);
                }

                $liquidacionId = DB::table('liquidaciones_comision')->insertGetId([
                    'fkuser' => $comisiones->first()->fkuser,
                    'liquidada_por' => auth()->id(),
                    'total' => $comisiones->sum('comision_final'),
                    'fkmetodo' => $data['fkmetodo'],
                    'referencia' => $data['referencia'] ?? null,
                    'observacion' => $data['observacion'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'id_liquidacion');
                DB::table('liquidacion_comision_detalles')->insert($comisiones->map(fn ($c) => ['fkliquidacion' => $liquidacionId, 'fkcomision' => $c->id_comision])->all());
                Comision::whereIn('id_comision', $comisiones->pluck('id_comision'))->update(['estado' => Comision::ESTADO_LIQUIDADA, 'fecha_pago_real' => now()]);

                foreach ($comisiones as $comision) {
                    $this->auditService->registrarCreacion('comisiones', 'LiquidacionComision', (int) $liquidacionId, [
                        'fkuser' => $comision->fkuser,
                        'fkcomision' => $comision->id_comision,
                        'total_lote' => $comisiones->sum('comision_final'),
                    ], auth()->id());
                }
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError($request, $e->getMessage());
        }

        return back()->with('success', 'Comisiones seleccionadas liquidadas correctamente.');
    }

    protected function respuestaError(Request $request, string $mensaje)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $mensaje], 422);
        }

        return back()->withErrors(['error' => $mensaje]);
    }
}
