<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Comision;
use App\Models\Pago;
use App\Models\Venta;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    public function index(Request $request)
    {
        $caja = Caja::with(['movimientos', 'usuario', 'sede'])
            ->where('fksede', auth()->user()->fksede)
            ->whereNull('fecha_cierre')
            ->first();

        if ($caja) {
            $caja->load(['movimientos' => function ($q) {
                $q->orderByDesc('created_at');
            }]);
        }

        if ($request->expectsJson()) {
            return response()->json(['caja' => $caja]);
        }

        return view('caja.index', compact('caja'));
    }

    public function apertura(Request $request)
    {
        $request->validate([
            'monto_inicial' => 'required|numeric|min:0',
        ], [
            'monto_inicial.required' => 'El monto inicial es requerido',
            'monto_inicial.numeric' => 'El monto inicial debe ser un número',
            'monto_inicial.min' => 'El monto inicial no puede ser negativo',
        ]);

        $cajaAbierta = Caja::where('fksede', auth()->user()->fksede)
            ->whereNull('fecha_cierre')
            ->first();

        if ($cajaAbierta) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Ya existe una caja abierta para esta sede'], 422);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Ya existe una caja abierta para esta sede'])
                ->withInput();
        }

        $caja = Caja::create([
            'monto_inicial' => $request->monto_inicial,
            'fkuser' => auth()->id(),
            'fksede' => auth()->user()->fksede,
            'fecha_apertura' => now(),
            'estado' => 'abierta',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Caja aperturada exitosamente',
                'caja' => $caja,
            ]);
        }

        return redirect()->route('caja.index')
            ->with('success', 'Caja aperturada exitosamente');
    }

    public function cierre(Request $request, $id)
    {
        $caja = Caja::where('fksede', auth()->user()->fksede)
            ->whereNull('fecha_cierre')
            ->first();

        if (! $caja) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No hay una caja abierta para esta sede'], 422);
            }

            return redirect()->back()
                ->withErrors(['error' => 'No hay una caja abierta para esta sede']);
        }

        $ingresos = $caja->movimientos()->where('tipo', 'ingreso')->sum('monto');
        $egresos = $caja->movimientos()->where('tipo', 'egreso')->sum('monto');
        $montoFinal = $caja->monto_inicial + $ingresos - $egresos;

        $caja->monto_final = $montoFinal;
        $caja->fecha_cierre = now();
        $caja->estado = 'cerrada';
        $caja->save();

        $ventas = Venta::where('fksede', $caja->fksede)
            ->where('estado_venta', 'completado')
            ->whereBetween('created_at', [$caja->fecha_apertura, $caja->fecha_cierre])
            ->get();

        $pagos = Pago::where('fksede', $caja->fksede)
            ->where('estado_pago', 'completo')
            ->whereBetween('created_at', [$caja->fecha_apertura, $caja->fecha_cierre])
            ->get();

        $usuariosVentas = $ventas->groupBy('fkusers');
        foreach ($usuariosVentas as $userId => $ventasUsuario) {
            $totalVentas = $ventasUsuario->sum('venta_total');
            Comision::create([
                'fkuser' => $userId,
                'fkcaja' => $caja->id_caja,
                'tipo' => 'venta',
                'porcentaje' => 10,
                'monto' => $totalVentas * 0.10,
            ]);
        }

        $usuariosPagos = $pagos->groupBy('fkuser');
        foreach ($usuariosPagos as $userId => $pagosUsuario) {
            $totalPagos = $pagosUsuario->sum('pag_monto');
            Comision::create([
                'fkuser' => $userId,
                'fkcaja' => $caja->id_caja,
                'tipo' => 'membresia',
                'porcentaje' => 10,
                'monto' => $totalPagos * 0.10,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Caja cerrada exitosamente',
                'caja' => $caja->fresh()->load('movimientos'),
            ]);
        }

        return redirect()->route('caja.index')
            ->with('success', 'Caja cerrada exitosamente');
    }
}
