<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Sede;
use App\Services\CashClosingService;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    protected CashClosingService $cashClosingService;

    public function __construct(CashClosingService $cashClosingService)
    {
        $this->cashClosingService = $cashClosingService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Caja::class);

        $query = Caja::with(['usuario', 'sede']);
        $sedeSeleccionada = auth()->user()->hasRole('Administrador') ? $request->integer('sede') : auth()->user()->fksede;

        if (! auth()->user()->hasRole('Administrador')) {
            $query->where('fksede', auth()->user()->fksede);
        } elseif ($sedeSeleccionada) {
            $query->where('fksede', $sedeSeleccionada);
        }

        if ($request->has('estado') && $request->estado) {
            $query->where('estado', $request->estado);
        }

        $cajas = $query->orderByDesc('fecha_apertura')->paginate(15);

        $cajaAbierta = $sedeSeleccionada ? Caja::where('fksede', $sedeSeleccionada)
            ->where('estado', 'abierta')
            ->first() : null;
        $sedes = Sede::where('sede_estado', true)->orderBy('sede_nombre')->get();
        $consolidado = Caja::with('sede')->whereDate('fecha_apertura', today())->get()->groupBy('fksede')->map(fn ($cajas) => [
            'sede' => $cajas->first()->sede?->sede_nombre,
            'esperado' => $cajas->sum('total_ingresos_esperado'),
            'entregado' => $cajas->sum('monto_entregado'),
            'diferencia' => $cajas->sum('diferencia'),
        ]);

        if ($cajaAbierta) {
            $operaciones = $this->cashClosingService->obtenerOperaciones($cajaAbierta);
            $ventas = $this->cashClosingService->calcularVentas($cajaAbierta);
            $pagos = $this->cashClosingService->calcularPagos($cajaAbierta);
            $gastos = $this->cashClosingService->calcularGastosAprobados($cajaAbierta);
            $comisiones = $this->cashClosingService->calcularComisiones($cajaAbierta);
            $montoEsperado = $this->cashClosingService->calcularMontoEsperado($cajaAbierta);
        } else {
            $operaciones = [];
            $ventas = ['cantidad' => 0, 'total' => 0];
            $pagos = ['cantidad' => 0, 'total' => 0, 'por_metodo' => []];
            $gastos = ['cantidad' => 0, 'total' => 0];
            $comisiones = ['cantidad' => 0, 'total_base' => 0, 'total_penalizaciones' => 0, 'total_final' => 0];
            $montoEsperado = 0;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'cajas' => $cajas,
                'caja_abierta' => $cajaAbierta,
                'operaciones' => $operaciones,
                'ventas' => $ventas,
                'pagos' => $pagos,
                'gastos' => $gastos,
                'comisiones' => $comisiones,
                'monto_esperado' => $montoEsperado,
            ]);
        }

        return view('caja.index', compact(
            'cajas',
            'cajaAbierta',
            'operaciones',
            'ventas',
            'pagos',
            'gastos',
            'comisiones',
            'montoEsperado', 'sedes', 'sedeSeleccionada', 'consolidado'
        ));
    }

    public function apertura(Request $request)
    {
        $this->authorize('abrir', Caja::class);

        $request->validate([
            'monto_inicial' => 'required|numeric|min:0',
            'fksede' => auth()->user()->hasRole('Administrador') ? 'required|exists:sedes,id_sede' : 'nullable',
        ], [
            'monto_inicial.required' => 'El monto inicial es requerido.',
            'monto_inicial.numeric' => 'El monto inicial debe ser un número.',
            'monto_inicial.min' => 'El monto inicial no puede ser negativo.',
        ]);

        $sedeId = auth()->user()->hasRole('Administrador') ? $request->integer('fksede') : auth()->user()->fksede;
        $cajaAbierta = Caja::where('fksede', $sedeId)
            ->where('estado', 'abierta')
            ->first();

        if ($cajaAbierta) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Ya existe una caja abierta para esta sede.'], 422);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Ya existe una caja abierta para esta sede.'])
                ->withInput();
        }

        $caja = Caja::create([
            'monto_inicial' => $request->monto_inicial,
            'fkuser' => auth()->id(),
            'fksede' => $sedeId,
            'fecha_apertura' => now(),
            'estado' => 'abierta',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Caja aperturada exitosamente.',
                'caja' => $caja,
            ], 201);
        }

        return redirect()->route('caja.index')
            ->with('success', 'Caja aperturada exitosamente.');
    }

    public function cierre(Request $request, $id)
    {
        $caja = Caja::findOrFail($id);
        $this->authorize('cerrar', $caja);

        $request->validate([
            'monto_entregado' => 'required|numeric|min:0',
        ], [
            'monto_entregado.required' => 'El monto entregado es requerido.',
            'monto_entregado.numeric' => 'El monto entregado debe ser un número.',
            'monto_entregado.min' => 'El monto entregado no puede ser negativo.',
        ]);

        $caja = $this->cashClosingService->cerrarCaja($caja, $request->monto_entregado);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Caja cerrada exitosamente.',
                'caja' => $caja,
            ]);
        }

        return redirect()->route('caja.index')
            ->with('success', 'Caja cerrada exitosamente.');
    }

    public function pdf($id)
    {
        $caja = Caja::findOrFail($id);
        $this->authorize('verPdf', $caja);

        $pdf = $this->cashClosingService->generarPdf($caja);

        return $pdf->download('cierre_caja_'.$caja->id_caja.'_'.date('Y-m-d').'.pdf');
    }

    public function anular(Request $request, $id)
    {
        $caja = Caja::findOrFail($id);
        $this->authorize('anular', $caja);

        $caja->update([
            'estado' => 'anulada',
            'observacion' => $request->input('observacion', 'Caja anulada por administrador.'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Caja anulada exitosamente.',
                'caja' => $caja,
            ]);
        }

        return redirect()->route('caja.index')
            ->with('success', 'Caja anulada exitosamente.');
    }
}
