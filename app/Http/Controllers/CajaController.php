<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Comision;
use App\Models\Sede;
use App\Services\AuditService;
use App\Services\CashClosingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CajaController extends Controller
{
    protected CashClosingService $cashClosingService;

    public function __construct(CashClosingService $cashClosingService, private readonly AuditService $auditService)
    {
        $this->cashClosingService = $cashClosingService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Caja::class);

        $esAdmin = auth()->user()->hasRole('Administrador');
        $this->cashClosingService->marcarVencidas($esAdmin ? null : auth()->id());
        $query = Caja::with(['usuario', 'sede', 'revisadaPor', 'detallesCierre.metodo'])
            ->withCount([
                'gastos as gastos_pendientes_count' => fn ($q) => $q->where('estado', 'pendiente'),
                'comisiones as comisiones_sin_resolver_count' => fn ($q) => $q->whereIn('estado', Comision::ESTADOS_BLOQUEAN_CIERRE),
            ]);

        if (! $esAdmin) {
            // El empleado solo consulta sus propias cajas.
            $query->where('fkuser', auth()->id());
        } else {
            if ($request->filled('sede')) {
                $query->where('fksede', $request->integer('sede'));
            }
            if ($request->filled('empleado')) {
                $query->where('fkuser', $request->integer('empleado'));
            }
            // Cola de revisión: por defecto solo pendientes de revisión.
            if (! $request->filled('estado')) {
                $query->where('estado', 'pendiente_revision');
            }
        }

        if ($request->has('estado') && $request->estado) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('fecha')) {
            $query->whereDate('fecha_operativa', $request->date('fecha'));
        }

        // La cola administrativa muestra primero los pendientes más antiguos.
        $cajas = $esAdmin
            ? $query->orderBy('fecha_operativa')->orderBy('id_caja')->paginate(15)->withQueryString()
            : $query->orderByDesc('fecha_apertura')->paginate(15)->withQueryString();

        $cajaAbierta = $this->cajaAbiertaEnRevision($request, $esAdmin);
        $cajaPropiaAbierta = $this->cashClosingService->cajaOperativaDe(auth()->id());
        $bloqueoApertura = $this->cashClosingService->impedimentoAperturaDe(auth()->id());
        $metodosCierre = $this->cashClosingService->metodosParaCierre();

        $sedes = Sede::where('sede_estado', true)->orderBy('sede_nombre')->get();
        $empleados = $esAdmin
            ? \App\Models\User::where('estado', true)->orderBy('name')->get(['id', 'name', 'fksede'])
            : collect();
        $consolidado = $this->consolidadoDelDia($esAdmin);

        if ($cajaAbierta) {
            $operaciones = $this->cashClosingService->obtenerOperaciones($cajaAbierta);
            $ventas = $this->cashClosingService->calcularVentas($cajaAbierta);
            $pagos = $this->cashClosingService->calcularPagos($cajaAbierta);
            $gastos = $this->cashClosingService->calcularGastosAprobados($cajaAbierta);
            $comisiones = $this->cashClosingService->calcularComisiones($cajaAbierta);
            $montoEsperado = $this->cashClosingService->calcularMontoEsperado($cajaAbierta);
            $tieneGastosPendientes = $this->cashClosingService->tieneGastosPendientes($cajaAbierta);
            $comisionesBloqueantes = ($esAdmin && $cajaAbierta->estado === 'pendiente_revision')
                ? $this->cashClosingService->comisionesBloqueantes($cajaAbierta)
                : collect();
            $gastosPendientes = ($esAdmin && $cajaAbierta->estado === 'pendiente_revision')
                ? \App\Models\Gasto::with(['categoria', 'metodo', 'user'])
                    ->where('fkcaja', $cajaAbierta->id_caja)
                    ->where('estado', 'pendiente')
                    ->orderBy('gas_fecha')
                    ->get()
                : collect();
        } else {
            $operaciones = [];
            $ventas = ['cantidad' => 0, 'total' => 0];
            $pagos = ['cantidad' => 0, 'total' => 0, 'por_metodo' => []];
            $gastos = ['cantidad' => 0, 'total' => 0];
            $comisiones = ['cantidad' => 0, 'total_base' => 0, 'total_penalizaciones' => 0, 'total_final' => 0];
            $montoEsperado = 0;
            $tieneGastosPendientes = false;
            $comisionesBloqueantes = collect();
            $gastosPendientes = collect();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'cajas' => $cajas,
                'caja_abierta' => $cajaAbierta,
                'caja_propia_abierta' => $cajaPropiaAbierta,
                'operaciones' => $operaciones,
                'ventas' => $ventas,
                'pagos' => $pagos,
                'gastos' => $gastos,
                'comisiones' => $comisiones,
                'monto_esperado' => $montoEsperado,
                'bloqueo_apertura' => $bloqueoApertura,
                'comisiones_bloqueantes' => $comisionesBloqueantes,
                'gastos_pendientes' => $gastosPendientes,
            ]);
        }

        return view('caja.index', compact(
            'cajas',
            'cajaAbierta',
            'cajaPropiaAbierta',
            'operaciones',
            'ventas',
            'pagos',
            'gastos',
            'comisiones',
            'montoEsperado', 'sedes', 'empleados', 'consolidado', 'esAdmin', 'bloqueoApertura', 'metodosCierre', 'tieneGastosPendientes', 'comisionesBloqueantes', 'gastosPendientes'
        ));
    }

    /**
     * Caja abierta en revisión: la propia para el empleado; para el
     * Administrador, la indicada por parámetro o la primera abierta.
     */
    protected function cajaAbiertaEnRevision(Request $request, bool $esAdmin): ?Caja
    {
        if (! $esAdmin) {
            return Caja::where('fkuser', auth()->id())
                ->whereIn('estado', ['abierta', 'pendiente_cierre', 'observada', 'pendiente_revision'])
                ->orderByDesc('fecha_operativa')
                ->first();
        }

        if ($request->filled('caja')) {
            $caja = Caja::find($request->integer('caja'));

            if ($caja) {
                return $caja;
            }
        }

        $query = Caja::whereIn('estado', ['pendiente_revision', 'observada', 'pendiente_cierre', 'abierta']);

        if ($request->filled('sede')) {
            $query->where('fksede', $request->integer('sede'));
        }

        return $query->orderByDesc('fecha_apertura')->first();
    }

    protected function consolidadoDelDia(bool $esAdmin)
    {
        $query = Caja::with('sede')->whereDate('fecha_operativa', today());

        if (! $esAdmin) {
            $query->where('fkuser', auth()->id());
        }

        return $query->get()->groupBy('fksede')->map(fn ($cajas) => [
            'sede' => $cajas->first()->sede?->sede_nombre,
            'esperado' => $cajas->sum('total_ingresos_esperado'),
            'entregado' => $cajas->sum('monto_entregado'),
            'diferencia' => $cajas->sum('diferencia'),
        ]);
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
        try {
            $caja = DB::transaction(function () use ($request, $sedeId) {
                \App\Models\User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
                $impedimento = $this->cashClosingService->impedimentoAperturaDe(auth()->id());
                if ($impedimento) {
                    throw ValidationException::withMessages(['error' => $impedimento]);
                }

                return Caja::create([
                    'monto_inicial' => $request->monto_inicial,
                    'fkuser' => auth()->id(),
                    'fksede' => $sedeId,
                    'fecha_apertura' => now(),
                    'fecha_operativa' => today(),
                    'estado' => 'abierta',
                ]);
            });
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->validator->errors()->first()], 422);
            }

            throw $e;
        }
        $this->auditService->registrarCreacion('caja', 'Caja', $caja->id_caja, $caja->toArray());

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

        $metodos = $this->cashClosingService->metodosParaCierre();
        $esCierreEnRepresentacion = (int) auth()->id() !== (int) $caja->fkuser;
        $reglas = [
            'observacion' => ($esCierreEnRepresentacion ? 'required' : 'nullable').'|string|max:1000',
        ];
        foreach ($metodos as $metodo) {
            $reglas['declarados.'.$metodo->id_metod] = 'required|numeric|min:0';
        }
        $datos = $request->validate($reglas, [
            'observacion.required' => 'Debes indicar el motivo del cierre en representación.',
            'declarados.*.required' => 'Debes declarar el importe de todos los métodos.',
            'declarados.*.numeric' => 'Cada importe declarado debe ser numérico.',
            'declarados.*.min' => 'Los importes declarados no pueden ser negativos.',
        ]);

        $declarados = collect($datos['declarados'])->mapWithKeys(fn ($monto, $metodo) => [(int) $metodo => (float) $monto])->all();
        $caja = $this->cashClosingService->enviarCierre($caja, $declarados, $datos['observacion'] ?? null);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cierre enviado para revisión.',
                'caja' => $caja,
            ]);
        }

        return redirect()->route('caja.index')
            ->with('success', 'Cierre enviado para revisión administrativa.');
    }

    public function aprobar(Request $request, $id)
    {
        $caja = Caja::findOrFail($id);
        $this->authorize('aprobar', $caja);
        $datos = $request->validate(['observacion_revision' => 'nullable|string|max:1000']);
        $this->cashClosingService->aprobarCierre($caja, auth()->id(), $datos['observacion_revision'] ?? null);

        return redirect()->route('caja.index', ['caja' => $caja->id_caja])
            ->with('success', 'Cierre de caja aprobado.');
    }

    public function observar(Request $request, $id)
    {
        $caja = Caja::findOrFail($id);
        $this->authorize('observar', $caja);
        $datos = $request->validate([
            'observacion_revision' => 'required|string|max:1000',
        ], ['observacion_revision.required' => 'Debes indicar el motivo de la observación.']);
        $this->cashClosingService->observarCierre($caja, auth()->id(), $datos['observacion_revision']);

        return redirect()->route('caja.index', ['caja' => $caja->id_caja])
            ->with('warning', 'El cierre fue observado y debe corregirse.');
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
        $anteriores = $caja->toArray();

        $caja->update([
            'estado' => 'anulada',
            'observacion' => $request->input('observacion', 'Caja anulada por administrador.'),
        ]);
        $this->auditService->registrarEliminacion('caja', 'Caja', $caja->id_caja, $anteriores);

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
