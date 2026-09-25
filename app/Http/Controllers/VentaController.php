<?php

namespace App\Http\Controllers;

use App\Http\Requests\VentaRequest;
use App\Models\Alumno;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Sede;
use App\Models\Venta;
use App\Services\AuditService;
use App\Services\CashClosingService;
use App\Services\CommissionService;
use App\Services\InventoryReservationService;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    protected SaleService $saleService;

    protected AuditService $auditService;

    public function __construct(
        SaleService $saleService,
        AuditService $auditService,
        private readonly CashClosingService $cashClosingService,
        private readonly InventoryReservationService $inventario,
        private readonly CommissionService $commissionService
    ) {
        $this->saleService = $saleService;
        $this->auditService = $auditService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Venta::class);

        $query = Venta::with(['alumno', 'user', 'sede', 'metodo', 'producto']);

        if (! auth()->user()->hasRole('Administrador')) {
            $query->where('fksede', auth()->user()->fksede);

            // Redes solo ve lo que él mismo registró.
            if (auth()->user()->hasRole('Redes')) {
                $query->where('fkusers', auth()->id());
            }
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
        $cajaPropiaAbierta = $this->cashClosingService->cajaOperativaDe(auth()->id());
        $bloqueoCaja = $cajaPropiaAbierta ? null : $this->cashClosingService->impedimentoAperturaDe(auth()->id());
        $sedes = Sede::where('sede_estado', true)->orderBy('sede_nombre')->get(['id_sede', 'sede_nombre']);

        if ($request->expectsJson()) {
            return response()->json($ventas);
        }

        return view('ventas.index', compact('ventas', 'cajaPropiaAbierta', 'bloqueoCaja', 'sedes'));
    }

    public function store(VentaRequest $request)
    {
        $this->authorize('create', Venta::class);

        $caja = $this->cashClosingService->cajaOperativaDe(auth()->id());

        if (! $caja) {
            $mensaje = $this->cashClosingService->impedimentoAperturaDe(auth()->id())
                ?? 'Debes aperturar tu caja antes de registrar una venta.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $mensaje], 422);
            }

            return redirect()->back()->withErrors(['error' => $mensaje])->withInput();
        }

        $data = $request->validated();
        $data['fkusers'] = auth()->id();
        // La sede de inscripción se elige en la venta de membresía;
        // productos y rápida operan con stock de la sede del empleado.
        $data['fksede'] = ($data['tipo_venta'] === 'membresia' && ! empty($data['fksede']))
            ? (int) $data['fksede']
            : auth()->user()->fksede;
        $data['fkcaja'] = $caja->id_caja;

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
            $this->inventario->devolverStockAlAnular($venta);
            $this->commissionService->anularComisionPorVenta($venta, auth()->id());
            $venta->update(['estado_venta' => 'anulado', 'motivo_anulacion' => $data['motivo_anulacion'], 'anulada_por' => auth()->id(), 'anulada_at' => now()]);
        });
        $this->auditService->registrarEdicion('ventas', 'Venta', $venta->id_venta, $valoresAnteriores, $venta->fresh()->toArray());

        return redirect()->route('ventas.index')->with('success', 'Venta anulada y stock restaurado.');
    }

    public function datosVentaRapida()
    {
        $metodos = MetodoPago::all(['id_metod', 'metod_nombre']);

        return response()->json([
            'metodos' => $metodos,
        ]);
    }

    public function datosVentaProducto()
    {
        $metodos = MetodoPago::all(['id_metod', 'metod_nombre']);

        return response()->json([
            'metodos' => $metodos,
        ]);
    }

    public function datosVentaMembresia()
    {
        $metodos = MetodoPago::all(['id_metod', 'metod_nombre']);

        return response()->json([
            'metodos' => $metodos,
        ]);
    }

    public function buscarAlumnos(Request $request)
    {
        $q = trim((string) $request->input('q'));
        if (! preg_match('/^\d{3,20}$/', $q)) {
            return response()->json([]);
        }

        $query = Alumno::where('alum_estado', true);
        if (! auth()->user()->hasRole('Administrador')) {
            $query->where('fksede', auth()->user()->fksede);
        }
        $query->where('alum_numDoc', 'like', "%{$q}%");

        return response()->json($query->orderBy('alum_numDoc')->limit(10)->get([
            'id_alumno', 'alum_nombre', 'alum_apellido', 'alum_numDoc',
        ]));
    }

    public function buscarProductos(Request $request)
    {
        $q = trim((string) $request->input('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $productos = Producto::where('fksede', auth()->user()->fksede)
            ->where('prod_estado', true)
            ->where('prod_nombre', 'like', "%{$q}%")
            ->orderBy('prod_nombre')
            ->limit(10)
            ->get(['id_productos', 'prod_nombre', 'prod_precio', 'prod_cantidad'])
            ->map(function (Producto $producto) {
                $producto->setAttribute('disponible', $producto->prod_cantidad > 0);

                return $producto;
            });

        return response()->json($productos);
    }

    public function buscarMembresias(Request $request)
    {
        $q = trim((string) $request->input('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(
            Membresia::where('estado', 'A')
                ->where('mem_nombre', 'like', "%{$q}%")
                ->orderBy('mem_nombre')
                ->limit(10)
                ->get([
                    'id_mem', 'mem_nombre', 'mem_precio', 'mem_duracion',
                    'modalidad', 'fecha_inicio_fija', 'fecha_fin_fija',
                ])
        );
    }
}
