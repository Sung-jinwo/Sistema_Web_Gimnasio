<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Caja;
use App\Models\Comision;
use App\Models\Gasto;
use App\Models\MembresiaAlumno;
use App\Models\User;
use App\Models\Venta;
use App\Services\FollowUpService;
use App\Services\NotificationService;

class DashboardController extends Controller
{
    protected FollowUpService $followUpService;

    protected NotificationService $notificationService;

    public function __construct(
        FollowUpService $followUpService,
        NotificationService $notificationService
    ) {
        $this->followUpService = $followUpService;
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole('Administrador')) {
            return $this->admin();
        }

        if ($user->hasRole('Local')) {
            return $this->local();
        }

        if ($user->hasRole('Redes')) {
            return $this->redes();
        }

        if ($user->hasRole('Asistencia')) {
            return $this->asistencia();
        }

        // Respaldo para usuarios legacy con columna `rol` pero sin rol Spatie asignado.
        switch ((int) $user->rol) {
            case User::ROL_ADMIN:
                return $this->admin();
            case User::ROL_EMPLEDO_LOCAL:
                return $this->local();
            case User::ROL_REDES:
                return $this->redes();
            case User::ROL_ASISTENCIA:
                return $this->asistencia();
        }

        abort(403, 'No tienes permiso para acceder al dashboard.');
    }

    protected function admin()
    {
        $hoy = now()->format('Y-m-d');
        $mesActual = now()->month;
        $anioActual = now()->year;

        $ventasHoy = Venta::whereDate('created_at', $hoy)
            ->where('estado_venta', 'completado')
            ->sum('venta_total');

        $ventasMes = Venta::whereMonth('created_at', $mesActual)
            ->whereYear('created_at', $anioActual)
            ->where('estado_venta', 'completado')
            ->sum('venta_total');

        $alumnosActivos = Alumno::where('alum_estado', true)->count();

        $membresiasActivas = MembresiaAlumno::where('estado', 'activa')
            ->where('fecha_fin', '>=', $hoy)
            ->count();

        $membresiasPorVencer = MembresiaAlumno::where('estado', 'activa')
            ->whereBetween('fecha_fin', [$hoy, now()->addDays(5)->format('Y-m-d')])
            ->count();

        $membresiasVencidas = MembresiaAlumno::where(function ($query) use ($hoy) {
            $query->where('estado', 'vencida')
                ->orWhere(function ($q) use ($hoy) {
                    $q->where('estado', 'activa')->where('fecha_fin', '<', $hoy);
                });
        })->count();

        $productosVendidos = Venta::whereMonth('created_at', $mesActual)
            ->whereYear('created_at', $anioActual)
            ->where('estado_venta', 'completado')
            ->whereIn('tipo_venta', ['producto', 'rapida'])
            ->count();

        $ingresosMes = Venta::whereMonth('created_at', $mesActual)
            ->whereYear('created_at', $anioActual)
            ->where('estado_venta', 'completado')
            ->sum('venta_total');

        $gastosMes = Gasto::whereMonth('gas_fecha', $mesActual)
            ->whereYear('gas_fecha', $anioActual)
            ->where('estado', 'aprobado')
            ->sum('gas_monto');

        $comisionesMes = Comision::whereMonth('created_at', $mesActual)
            ->whereYear('created_at', $anioActual)
            ->where('estado', '!=', Comision::ESTADO_ANULADA)
            ->sum('comision_final');

        $cierresPendientes = Caja::where('estado', 'abierta')->count();

        $asistenciasHoy = Asistencia::whereDate('visi_fecha', $hoy)->count();

        $mesAnterior = now()->subMonth();
        $ingresosMesAnterior = Venta::whereMonth('created_at', $mesAnterior->month)
            ->whereYear('created_at', $mesAnterior->year)
            ->where('estado_venta', 'completado')
            ->sum('venta_total');
        $variacionIngresos = $ingresosMesAnterior > 0
            ? round(($ingresosMes - $ingresosMesAnterior) / $ingresosMesAnterior * 100, 1)
            : null;

        $nuevosAlumnosMes = Alumno::whereMonth('created_at', $mesActual)
            ->whereYear('created_at', $anioActual)
            ->count();

        $graficoFinanzas = $this->serieIngresosVsGastos();
        $graficoMembresias = [
            'etiquetas' => ['Activas', 'Por vencer', 'Vencidas'],
            'datos' => [(int) $membresiasActivas, (int) $membresiasPorVencer, (int) $membresiasVencidas],
        ];
        $graficoVentasSede = $this->serieVentasPorSede($mesActual, $anioActual);

        return view('dashboard.admin', compact(
            'ventasHoy',
            'alumnosActivos',
            'nuevosAlumnosMes',
            'ingresosMes',
            'variacionIngresos',
            'membresiasPorVencer',
            'membresiasVencidas',
            'cierresPendientes',
            'graficoFinanzas',
            'graficoMembresias',
            'graficoVentasSede'
        ));
    }

    /**
     * Ingresos (ventas completadas) vs gastos aprobados de los últimos 6 meses.
     */
    protected function serieIngresosVsGastos(): array
    {
        $etiquetas = [];
        $ingresos = [];
        $gastos = [];

        for ($i = 5; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $etiquetas[] = ucfirst($fecha->locale('es')->shortMonthName);
            $ingresos[] = (float) Venta::whereMonth('created_at', $fecha->month)
                ->whereYear('created_at', $fecha->year)
                ->where('estado_venta', 'completado')
                ->sum('venta_total');
            $gastos[] = (float) Gasto::whereMonth('gas_fecha', $fecha->month)
                ->whereYear('gas_fecha', $fecha->year)
                ->where('estado', 'aprobado')
                ->sum('gas_monto');
        }

        return compact('etiquetas', 'ingresos', 'gastos');
    }

    /**
     * Ventas completadas del mes agrupadas por sede.
     */
    protected function serieVentasPorSede(int $mes, int $anio): array
    {
        $filas = Venta::with('sede:id_sede,sede_nombre')
            ->whereMonth('created_at', $mes)
            ->whereYear('created_at', $anio)
            ->where('estado_venta', 'completado')
            ->selectRaw('fksede, SUM(venta_total) as total')
            ->groupBy('fksede')
            ->get();

        return [
            'etiquetas' => $filas->map(fn ($f) => $f->sede?->sede_nombre ?? 'Sin sede')->all(),
            'datos' => $filas->map(fn ($f) => (float) $f->total)->all(),
        ];
    }

    protected function local()
    {
        $user = auth()->user();
        $sedeId = $user->fksede;
        $hoy = now()->format('Y-m-d');
        $mesActual = now()->month;
        $anioActual = now()->year;

        $ventasHoy = Venta::where('fksede', $sedeId)
            ->where('fkusers', $user->id)
            ->whereDate('created_at', $hoy)
            ->where('estado_venta', 'completado')
            ->sum('venta_total');

        $ventasMes = Venta::where('fksede', $sedeId)
            ->where('fkusers', $user->id)
            ->whereMonth('created_at', $mesActual)
            ->whereYear('created_at', $anioActual)
            ->where('estado_venta', 'completado')
            ->sum('venta_total');

        $alumnosSede = Alumno::where('fksede', $sedeId)
            ->where('alum_estado', true)
            ->count();

        $membresiasPorVencer = MembresiaAlumno::whereHas('alumno', function ($query) use ($sedeId) {
            $query->where('fksede', $sedeId);
        })
            ->where('estado', 'activa')
            ->whereBetween('fecha_fin', [$hoy, now()->addDays(5)->format('Y-m-d')])
            ->count();

        $pagosPendientes = Venta::where('fksede', $sedeId)
            ->where('fkusers', $user->id)
            ->whereIn('estado_pago', ['parcial', 'pendiente'])
            ->count();

        $cajaAbierta = Caja::where('fksede', $sedeId)
            ->where('fkuser', $user->id)
            ->where('estado', 'abierta')
            ->exists();

        $totalNoLeidas = $this->notificationService->contarNoLeidas($user->id);

        return view('dashboard.local', compact(
            'ventasHoy',
            'ventasMes',
            'alumnosSede',
            'membresiasPorVencer',
            'pagosPendientes',
            'cajaAbierta',
            'totalNoLeidas'
        ));
    }

    protected function redes()
    {
        $user = auth()->user();
        $sedeId = $user->fksede;
        $hoy = now()->format('Y-m-d');
        $mesActual = now()->month;
        $anioActual = now()->year;

        $alumnosGestionados = Alumno::where('fksede', $sedeId)
            ->where('fkuser', $user->id)
            ->where('alum_estado', true)
            ->count();

        $nuevosAlumnosMes = Alumno::where('fksede', $sedeId)
            ->where('fkuser', $user->id)
            ->whereMonth('created_at', $mesActual)
            ->count();

        $membresiasPorVencer = MembresiaAlumno::whereHas('alumno', function ($query) use ($sedeId, $user) {
            $query->where('fksede', $sedeId)
                ->where('fkuser', $user->id);
        })
            ->where('estado', 'activa')
            ->whereBetween('fecha_fin', [$hoy, now()->addDays(5)->format('Y-m-d')])
            ->count();

        $membresiasVencidas = MembresiaAlumno::whereHas('alumno', function ($query) use ($sedeId, $user) {
            $query->where('fksede', $sedeId)
                ->where('fkuser', $user->id);
        })
            ->where(function ($query) use ($hoy) {
                $query->where('estado', 'vencida')
                    ->orWhere(function ($q) use ($hoy) {
                        $q->where('estado', 'activa')->where('fecha_fin', '<', $hoy);
                    });
            })
            ->count();

        $seguimientosPendientes = $membresiasPorVencer + $membresiasVencidas;

        $ventasHoy = Venta::where('fkusers', $user->id)
            ->whereDate('created_at', $hoy)
            ->where('estado_venta', 'completado')
            ->sum('venta_total');

        $ventasMes = Venta::where('fkusers', $user->id)
            ->whereMonth('created_at', $mesActual)
            ->whereYear('created_at', $anioActual)
            ->where('estado_venta', 'completado')
            ->sum('venta_total');

        $comisionMes = Comision::where('fkuser', $user->id)
            ->where('estado', '!=', Comision::ESTADO_ANULADA)
            ->whereMonth('created_at', $mesActual)
            ->whereYear('created_at', $anioActual)
            ->sum('comision_final');

        $cajaAbierta = Caja::where('fkuser', $user->id)
            ->where('estado', 'abierta')
            ->exists();

        $totalNoLeidas = $this->notificationService->contarNoLeidas($user->id);

        $graficoMisVentas = $this->serieMisVentasSemanales($user->id, $mesActual, $anioActual);
        $graficoSeguimiento = [
            'etiquetas' => ['Por vencer', 'Vencidas'],
            'datos' => [(int) $membresiasPorVencer, (int) $membresiasVencidas],
        ];

        return view('dashboard.redes', compact(
            'alumnosGestionados',
            'nuevosAlumnosMes',
            'membresiasPorVencer',
            'membresiasVencidas',
            'seguimientosPendientes',
            'ventasHoy',
            'ventasMes',
            'comisionMes',
            'cajaAbierta',
            'totalNoLeidas',
            'graficoMisVentas',
            'graficoSeguimiento'
        ));
    }

    /**
     * Ventas propias completadas del mes agrupadas por semana (1-7, 8-14, 15-21, 22-fin).
     */
    protected function serieMisVentasSemanales(int $userId, int $mes, int $anio): array
    {
        $ultimoDia = now()->setDate($anio, $mes, 1)->endOfMonth()->day;
        $cortes = [[1, 7], [8, 14], [15, 21], [22, $ultimoDia]];
        $datos = [];

        foreach ($cortes as [$desde, $hasta]) {
            $datos[] = (float) Venta::where('fkusers', $userId)
                ->whereYear('created_at', $anio)
                ->whereMonth('created_at', $mes)
                ->whereDay('created_at', '>=', $desde)
                ->whereDay('created_at', '<=', $hasta)
                ->where('estado_venta', 'completado')
                ->sum('venta_total');
        }

        return [
            'etiquetas' => ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
            'datos' => $datos,
        ];
    }

    protected function asistencia()
    {
        $hoy = now()->format('Y-m-d');
        $asistenciasHoy = Asistencia::whereDate('visi_fecha', $hoy)->count();

        return view('dashboard.asistencia', compact('asistenciasHoy'));
    }

    public function reportes()
    {
        return view('reporte.index');
    }

    public function reportesVentas()
    {
        return view('ventas.index');
    }

    public function formulario()
    {
        return view('reporte.formulario');
    }

    public function graficos()
    {
        return view('graficos.index');
    }
}
