<?php

namespace App\Http\Controllers;

use App\Http\Requests\VentaRequest;
use App\Models\Alumno;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\AuditService;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    protected SaleService $saleService;

    protected AuditService $auditService;

    public function __construct(SaleService $saleService, AuditService $auditService)
    {
        $this->saleService = $saleService;
        $this->auditService = $auditService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Venta::class);

        $query = Venta::with(['alumno', 'user', 'sede', 'metodo', 'producto']);

        if (! auth()->user()->hasRole('Administrador')) {
            $query->where('fksede', auth()->user()->fksede);
        }

        if ($request->has('search') && $request->search) {
            $query->whereHas('alumno', function ($q) use ($request) {
                $q->where('alum_nombre', 'like', '%'.$request->search.'%')
                    ->orWhere('alum_apellido', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('tipo_venta') && $request->tipo_venta) {
            $query->where('tipo_venta', $request->tipo_venta);
        }

        if ($request->has('estado_venta') && $request->estado_venta) {
            $query->where('estado_venta', $request->estado_venta);
        }

        if ($request->has('estado_pago') && $request->estado_pago) {
            $query->where('estado_pago', $request->estado_pago);
        }

        $ventas = $query->orderByDesc('updated_at')->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($ventas);
        }

        return view('ventas.index', compact('ventas'));
    }

    public function store(VentaRequest $request)
    {
        $this->authorize('create', Venta::class);

        $data = $request->validated();
        $data['fkusers'] = auth()->id();
        $data['fksede'] = auth()->user()->fksede;

        try {
            $venta = null;

            switch ($data['tipo_venta']) {
                case 'producto':
                    $venta = $this->saleService->crearVentaProducto($data);
                    break;
                case 'membresia':
                    $venta = $this->saleService->crearVentaMembresia($data);
                    break;
                case 'rapida':
                    $venta = $this->saleService->crearVentaRapida($data);
                    break;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Venta registrada exitosamente',
                    'venta' => $venta->load(['alumno', 'metodo', 'producto']),
                ], 201);
            }

            return redirect()->route('ventas.index')
                ->with('success', 'Venta registrada exitosamente');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $venta = Venta::findOrFail($id);
        $this->authorize('update', $venta);

        abort_unless($venta->estado_venta === 'reservado' || auth()->user()->hasRole('Administrador'), 422, 'Solo se pueden editar completamente las ventas reservadas.');
        $data = $request->validate([
            'estado_venta' => 'required|in:completado,reservado',
            'monto_pagado' => 'nullable|numeric|min:0',
            'fecha_acordada' => 'nullable|date',
            'observacion' => 'nullable|string',
        ]);

        if (isset($data['monto_pagado'])) {
            $data['saldo'] = $venta->venta_total - $data['monto_pagado'];
            $data['estado_pago'] = $data['saldo'] <= 0 ? 'pagado' : ($data['monto_pagado'] > 0 ? 'parcial' : 'pendiente');
            if ($data['saldo'] <= 0) {
                $data['fecha_acordada'] = null;
            }
        }

        $venta->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Venta actualizada exitosamente',
                'venta' => $venta->fresh()->load(['alumno', 'metodo', 'producto']),
            ]);
        }

        return redirect()->route('ventas.index')
            ->with('success', 'Venta actualizada exitosamente');
    }

    public function destroy(Request $request, $id)
    {
        return $this->anular($request, $id);
    }

    public function anular(Request $request, $id)
    {
        $venta = Venta::with(['detalles.producto', 'comisiones'])->findOrFail($id);
        $this->authorize('delete', $venta);
        $data = $request->validate(['motivo_anulacion' => 'required|string|max:500']);
        if ($venta->estado_venta === 'anulado') {
            return back()->withErrors(['venta' => 'La venta ya está anulada.']);
        }
        if ($venta->comisiones()->where('estado', 'liquidada')->exists()) {
            return back()->withErrors(['venta' => 'No se puede anular una venta con comisión liquidada.']);
        }
        $valoresAnteriores = $venta->toArray();
        DB::transaction(function () use ($venta, $data) {
            foreach ($venta->detalles as $detalle) {
                $detalle->producto?->increment('prod_cantidad', $detalle->cantidad);
            }
            $venta->comisiones()->delete();
            $venta->update(['estado_venta' => 'anulado', 'motivo_anulacion' => $data['motivo_anulacion'], 'anulada_por' => auth()->id(), 'anulada_at' => now()]);
        });
        $this->auditService->registrarEdicion('ventas', 'Venta', $venta->id_venta, $valoresAnteriores, $venta->fresh()->toArray());

        return redirect()->route('ventas.index')->with('success', 'Venta anulada y stock restaurado.');
    }

    public function reservados(Request $request)
    {
        $this->authorize('viewAny', Venta::class);

        $query = Venta::with(['alumno', 'user', 'sede', 'metodo', 'producto'])
            ->where('estado_venta', 'reservado');

        if (! auth()->user()->hasRole('Administrador')) {
            $query->where('fksede', auth()->user()->fksede);
        }

        $ventas = $query->orderByDesc('updated_at')->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($ventas);
        }

        return view('ventas.index', compact('ventas'))->with('soloReservadas', true);
    }

    public function datosVentaRapida()
    {
        $productos = Producto::where('fksede', auth()->user()->fksede)->where('prod_estado', true)
            ->where('prod_cantidad', '>', 0)
            ->get(['id_productos', 'prod_nombre', 'prod_precio', 'prod_cantidad']);
        $metodos = MetodoPago::all(['id_metod', 'metod_nombre']);

        return response()->json([
            'productos' => $productos,
            'metodos' => $metodos,
        ]);
    }

    public function datosVentaProducto()
    {
        $alumnos = Alumno::where('fksede', auth()->user()->fksede)
            ->where('alum_estado', true)
            ->orderBy('alum_nombre')
            ->get(['id_alumno', 'alum_nombre', 'alum_apellido', 'alum_numDoc']);
        $productos = Producto::where('fksede', auth()->user()->fksede)->where('prod_estado', true)
            ->where('prod_cantidad', '>', 0)
            ->get(['id_productos', 'prod_nombre', 'prod_precio', 'prod_cantidad']);
        $metodos = MetodoPago::all(['id_metod', 'metod_nombre']);

        return response()->json([
            'alumnos' => $alumnos,
            'productos' => $productos,
            'metodos' => $metodos,
        ]);
    }

    public function datosVentaMembresia()
    {
        $alumnos = Alumno::where('fksede', auth()->user()->fksede)
            ->where('alum_estado', true)
            ->orderBy('alum_nombre')
            ->get(['id_alumno', 'alum_nombre', 'alum_apellido', 'alum_numDoc']);
        $membresias = Membresia::where('estado', 'A')
            ->get(['id_mem', 'mem_nombre', 'mem_precio', 'mem_duracion', 'modalidad', 'fecha_inicio_fija', 'fecha_fin_fija']);
        $metodos = MetodoPago::all(['id_metod', 'metod_nombre']);

        return response()->json([
            'alumnos' => $alumnos,
            'membresias' => $membresias,
            'metodos' => $metodos,
        ]);
    }

    public function buscarAlumnos(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $query = Alumno::where('alum_estado', true);
        if (! auth()->user()->hasRole('Administrador')) {
            $query->where('fksede', auth()->user()->fksede);
        }
        if ($q !== '') {
            $query->where(fn ($x) => $x->where('alum_numDoc', 'like', "%{$q}%")->orWhere('alum_codigo', 'like', "%{$q}%")->orWhere('alum_nombre', 'like', "%{$q}%")->orWhere('alum_apellido', 'like', "%{$q}%"));
        }

        return response()->json($query->limit(10)->get(['id_alumno', 'alum_nombre', 'alum_apellido', 'alum_numDoc', 'alum_codigo']));
    }
}
