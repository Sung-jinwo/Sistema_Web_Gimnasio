<?php

namespace App\Http\Controllers;

use App\Http\Requests\AbonoRequest;
use App\Models\Abono;
use App\Models\Cuota;
use App\Models\MetodoPago;
use App\Models\Sede;
use App\Models\Venta;
use App\Services\CashClosingService;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class CobranzaController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly CashClosingService $cashClosingService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Venta::class);

        $query = Venta::with(['alumno', 'sede', 'cuotas', 'membresia', 'membresiaAlumno.membresia', 'detalles.producto'])
            ->where('saldo', '>', 0)
            ->where('estado_venta', '!=', 'anulado');

        $this->aplicarFiltrosVenta($query, $request);

        return view('cobranza.index', [
            'ventas' => $query->orderBy('fecha_acordada')->orderByDesc('created_at')->paginate(15)->withQueryString(),
            'metodos' => MetodoPago::orderBy('metod_nombre')->get(),
            'sedes' => auth()->user()->hasRole('Administrador') ? Sede::orderBy('sede_nombre')->get() : collect(),
        ]);
    }

    public function vencidas(Request $request)
    {
        $this->authorize('viewAny', Venta::class);

        $query = Cuota::with(['venta.alumno', 'venta.sede'])
            ->whereNotNull('fkventa')
            ->whereHas('venta', fn ($q) => $q->where('estado_venta', '!=', 'anulado'))
            ->where(function ($q) {
                $q->where('estado', 'vencida')
                    ->orWhere(fn ($subquery) => $subquery->whereIn('estado', ['pendiente', 'parcial'])
                        ->whereDate('fecha_acordada', '<', today()));
            });

        if (! auth()->user()->hasRole('Administrador')) {
            $query->whereHas('venta', fn ($q) => $q->where('fksede', auth()->user()->fksede));

            // Redes solo ve las cuotas de lo que él mismo registró.
            if (auth()->user()->hasRole('Redes')) {
                $query->whereHas('venta', fn ($q) => $q->where('fkusers', auth()->id()));
            }
        } elseif ($request->filled('sede')) {
            $query->whereHas('venta', fn ($q) => $q->where('fksede', $request->integer('sede')));
        }

        return view('cobranza.vencidas', [
            'cuotas' => $query->orderBy('fecha_acordada')->paginate(15)->withQueryString(),
        ]);
    }

    public function historial(Request $request)
    {
        $this->authorize('viewAny', Venta::class);

        $query = Abono::with(['venta.alumno', 'venta.membresiaAlumno.membresia', 'metodo', 'user', 'sede']);

        if (! auth()->user()->hasRole('Administrador')) {
            $query->where('fksede', auth()->user()->fksede);

            // Redes solo ve los abonos que él mismo registró.
            if (auth()->user()->hasRole('Redes')) {
                $query->where('fkuser', auth()->id());
            }
        } elseif ($request->filled('sede')) {
            $query->where('fksede', $request->integer('sede'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->whereHas('venta.alumno', fn ($q) => $q->where('alum_nombre', 'like', "%{$search}%")
                ->orWhere('alum_apellido', 'like', "%{$search}%")
                ->orWhere('alum_numDoc', 'like', "%{$search}%"));
        }

        return view('cobranza.historial', [
            'abonos' => $query->orderByDesc('fecha_abono')->paginate(15)->withQueryString(),
            'sedes' => auth()->user()->hasRole('Administrador') ? Sede::orderBy('sede_nombre')->get() : collect(),
        ]);
    }

    public function abonar(AbonoRequest $request, Venta $venta)
    {
        $this->authorize('update', $venta);
        abort_if($venta->stock_liberado_at && ! auth()->user()->hasRole('Administrador'), 403, 'Solo el Administrador puede cobrar una reserva cuyo stock fue liberado.');

        $caja = $this->cashClosingService->cajaOperativaDe(auth()->id());
        abort_if(! $caja, 422, $this->cashClosingService->impedimentoAperturaDe(auth()->id())
            ?? 'Debes aperturar tu caja antes de registrar un abono.');

        $this->paymentService->registrarAbono($venta->id_venta, $request->validated() + [
            'fkuser' => auth()->id(),
            'fksede' => $venta->fksede,
            'fkcaja' => $caja->id_caja,
        ]);

        return back()->with('success', 'Abono registrado exitosamente.');
    }

    private function aplicarFiltrosVenta($query, Request $request): void
    {
        if (! auth()->user()->hasRole('Administrador')) {
            $query->where('fksede', auth()->user()->fksede);

            // Redes solo gestiona los saldos de lo que él mismo registró.
            if (auth()->user()->hasRole('Redes')) {
                $query->where('fkusers', auth()->id());
            }
        } elseif ($request->filled('sede')) {
            $query->where('fksede', $request->integer('sede'));
        }

        if ($request->filled('tipo_venta')) {
            $query->where('tipo_venta', $request->string('tipo_venta'));
        }

        if ($request->filled('estado_pago')) {
            if ($request->string('estado_pago')->toString() === 'vencido') {
                $query->whereHas('cuotas', fn ($q) => $q->whereIn('estado', ['pendiente', 'parcial', 'vencida'])
                    ->whereDate('fecha_acordada', '<', today()));
            } else {
                $query->where('estado_pago', $request->string('estado_pago'));
            }
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->whereHas('alumno', fn ($q) => $q->where('alum_nombre', 'like', "%{$search}%")
                ->orWhere('alum_apellido', 'like', "%{$search}%")
                ->orWhere('alum_numDoc', 'like', "%{$search}%"));
        }
    }
}
