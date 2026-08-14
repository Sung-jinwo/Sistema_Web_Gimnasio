<?php

namespace App\Http\Controllers;

use App\Http\Requests\PagoRequest;
use App\Models\Alumno;
use App\Models\Cuota;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Pago::class);

        $query = Pago::with(['alumno', 'membresia', 'metodo', 'user', 'sede', 'cuotas']);

        if (!auth()->user()->hasRole('Administrador')) {
            $query->where('fksede', auth()->user()->fksede);
        }

        if ($request->has('search') && $request->search) {
            $query->whereHas('alumno', function ($q) use ($request) {
                $q->where('alum_nombre', 'like', '%'.$request->search.'%')
                    ->orWhere('alum_apellido', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('estado_pago') && $request->estado_pago) {
            $query->where('estado_pago', $request->estado_pago);
        }

        $pagos = $query->orderByDesc('updated_at')->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($pagos);
        }

        return view('pagos.index', compact('pagos'));
    }

    public function store(PagoRequest $request)
    {
        $this->authorize('create', Pago::class);

        $data = $request->validated();
        $data['fkuser'] = auth()->id();
        $data['fksede'] = auth()->user()->fksede;
        $data['total'] = $data['total'] ?? $data['pag_monto'];
        $data['monto_pagado'] = $data['monto_pagado'] ?? $data['pag_monto'];
        $data['saldo'] = max(0, $data['total'] - $data['monto_pagado']);

        $pago = Pago::create($data);

        if ($data['saldo'] > 0 && isset($data['fecha_limite_pago'])) {
            Cuota::create([
                'fkpago' => $pago->id_pag,
                'numero_cuota' => 1,
                'monto' => $data['saldo'],
                'monto_pagado' => 0,
                'saldo' => $data['saldo'],
                'fecha_acordada' => $data['fecha_limite_pago'],
                'estado' => 'pendiente',
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pago registrado exitosamente',
                'pago' => $pago->load(['alumno', 'membresia', 'metodo', 'cuotas']),
            ], 201);
        }

        return redirect()->route('pagos.index')
            ->with('success', 'Pago registrado exitosamente');
    }

    public function edit($id)
    {
        $pago = Pago::findOrFail($id);
        $this->authorize('update', $pago);

        if (request()->expectsJson()) {
            return response()->json($pago->load('cuotas'));
        }

        $alumnos = Alumno::where('fksede', auth()->user()->fksede)
            ->orderBy('alum_nombre')
            ->get();
        $membresias = Membresia::all();
        $metodos = MetodoPago::all();

        return view('pagos.edit', compact('pago', 'alumnos', 'membresias', 'metodos'));
    }

    public function update(PagoRequest $request, $id)
    {
        $pago = Pago::findOrFail($id);
        $this->authorize('update', $pago);

        $data = $request->validated();

        if (!isset($data['fkuser'])) {
            $data['fkuser'] = $pago->fkuser ?? auth()->id();
        }

        if (isset($data['total'])) {
            $data['saldo'] = max(0, $data['total'] - ($data['monto_pagado'] ?? 0));
        }

        $pago->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pago actualizado exitosamente',
                'pago' => $pago->fresh()->load(['alumno', 'membresia', 'metodo', 'cuotas']),
            ]);
        }

        return redirect()->route('pagos.index')
            ->with('success', 'Pago actualizado exitosamente');
    }

    public function destroy(Request $request, $id)
    {
        $pago = Pago::findOrFail($id);
        $this->authorize('delete', $pago);

        $pago->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pago eliminado exitosamente',
            ]);
        }

        return redirect()->route('pagos.index')
            ->with('success', 'Pago eliminado exitosamente');
    }

    public function completos(Request $request)
    {
        $this->authorize('viewAny', Pago::class);

        $query = Pago::with(['alumno', 'membresia', 'metodo', 'user', 'sede'])
            ->where('estado_pago', 'completo');

        if (!auth()->user()->hasRole('Administrador')) {
            $query->where('fksede', auth()->user()->fksede);
        }

        $pagos = $query->orderByDesc('updated_at')->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($pagos);
        }

        return view('pagos.completos', compact('pagos'));
    }

    public function incompletos(Request $request)
    {
        $this->authorize('viewAny', Pago::class);

        $query = Pago::with(['alumno', 'membresia', 'metodo', 'user', 'sede', 'cuotas'])
            ->whereIn('estado_pago', ['incompleto', 'reservado']);

        if (!auth()->user()->hasRole('Administrador')) {
            $query->where('fksede', auth()->user()->fksede);
        }

        $pagos = $query->orderByDesc('updated_at')->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($pagos);
        }

        return view('pagos.incompletos', compact('pagos'));
    }

    public function registrarCuota(Request $request, $pagoId)
    {
        $pago = Pago::findOrFail($pagoId);
        $this->authorize('update', $pago);

        $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'fecha_acordada' => 'required|date|after_or_equal:today',
        ]);

        $cuota = Cuota::create([
            'fkpago' => $pagoId,
            'numero_cuota' => Cuota::where('fkpago', $pagoId)->count() + 1,
            'monto' => $request->monto,
            'monto_pagado' => 0,
            'saldo' => $request->monto,
            'fecha_acordada' => $request->fecha_acordada,
            'estado' => 'pendiente',
        ]);

        $pago->update([
            'estado_pago' => 'incompleto',
            'fecha_limite_pago' => $request->fecha_acordada,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cuota registrada exitosamente',
                'cuota' => $cuota,
            ], 201);
        }

        return redirect()->route('pagos.incompletos')
            ->with('success', 'Cuota registrada exitosamente');
    }

    public function abonarCuota(Request $request, $cuotaId)
    {
        $cuota = Cuota::findOrFail($cuotaId);

        if ($cuota->fkpago) {
            $pago = Pago::find($cuota->fkpago);
            if ($pago) {
                $this->authorize('update', $pago);
            }
        }

        $request->validate([
            'monto' => 'required|numeric|min:0.01|max:' . $cuota->saldo,
        ]);

        $cuotaActualizada = $this->paymentService->aplicarPagoACuota($cuotaId, $request->monto);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Abono registrado exitosamente',
                'cuota' => $cuotaActualizada,
            ]);
        }

        return redirect()->route('pagos.incompletos')
            ->with('success', 'Abono registrado exitosamente');
    }

    public function cuotasVencidas(Request $request)
    {
        $this->authorize('viewAny', Pago::class);

        $query = Cuota::with(['pago.alumno', 'pago.membresia', 'venta.alumno'])
            ->where(function ($q) {
                $q->where('estado', 'vencida')
                  ->orWhere(function ($q2) {
                      $q2->where('estado', 'pendiente')
                         ->where('fecha_acordada', '<', now()->format('Y-m-d'));
                  });
            });

        $cuotas = $query->orderBy('fecha_acordada')->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($cuotas);
        }

        return view('pagos.cuotas_vencidas', compact('cuotas'));
    }
}
