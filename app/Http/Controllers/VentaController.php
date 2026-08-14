<?php

namespace App\Http\Controllers;

use App\Http\Requests\VentaRequest;
use App\Models\Alumno;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\SaleService;
use Illuminate\Http\Request;

class VentaController extends Controller
{
    protected SaleService $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Venta::class);

        $query = Venta::with(['alumno', 'user', 'sede', 'metodo', 'producto']);

        if (!auth()->user()->hasRole('Administrador')) {
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

        $data = $request->validate([
            'estado_venta' => 'required|in:completado,reservado,incompleto',
            'estado_pago' => 'required|in:pagado,parcial,pendiente',
            'monto_pagado' => 'nullable|numeric|min:0',
            'fecha_acordada' => 'nullable|date',
            'observacion' => 'nullable|string',
        ]);

        if (isset($data['monto_pagado'])) {
            $data['saldo'] = $venta->venta_total - $data['monto_pagado'];
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
        $venta = Venta::findOrFail($id);
        $this->authorize('delete', $venta);

        $venta->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Venta eliminada exitosamente',
            ]);
        }

        return redirect()->route('ventas.index')
            ->with('success', 'Venta eliminada exitosamente');
    }

    public function reservados(Request $request)
    {
        $this->authorize('viewAny', Venta::class);

        $query = Venta::with(['alumno', 'user', 'sede', 'metodo', 'producto'])
            ->where('estado_venta', 'reservado');

        if (!auth()->user()->hasRole('Administrador')) {
            $query->where('fksede', auth()->user()->fksede);
        }

        $ventas = $query->orderByDesc('updated_at')->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($ventas);
        }

        return view('ventas.reservados', compact('ventas'));
    }

    public function datosVentaRapida()
    {
        $productos = Producto::where('fksede', auth()->user()->fksede)
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
        $productos = Producto::where('fksede', auth()->user()->fksede)
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
            ->get(['id_mem', 'mem_nombre', 'mem_precio', 'mem_duracion']);
        $metodos = MetodoPago::all(['id_metod', 'metod_nombre']);

        return response()->json([
            'alumnos' => $alumnos,
            'membresias' => $membresias,
            'metodos' => $metodos,
        ]);
    }
}
