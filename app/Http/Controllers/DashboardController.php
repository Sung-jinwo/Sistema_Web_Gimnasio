<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Caja;
use App\Models\Comision;
use App\Models\Gasto;
use App\Models\MembresiaAlumno;
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
            ->sum('comision_final');

        $cierresPendientes = Caja::where('estado', 'abierta')->count();

        $asistenciasHoy = Asistencia::whereDate('visi_fecha', $hoy)->count();

        return view('dashboard.admin', compact(
            'ventasHoy',
            'ventasMes',
            'alumnosActivos',
            'membresiasActivas',
            'membresiasPorVencer',
            'membresiasVencidas',
            'productosVendidos',
            'ingresosMes',
            'gastosMes',
            'comisionesMes',
            'cierresPendientes',
            'asistenciasHoy'
        ));
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

        $comisionMes = Comision::where('fkuser', $user->id)
            ->whereMonth('created_at', $mesActual)
            ->whereYear('created_at', $anioActual)
            ->sum('comision_final');

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
            'comisionMes',
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

        $totalNoLeidas = $this->notificationService->contarNoLeidas($user->id);

        return view('dashboard.redes', compact(
            'alumnosGestionados',
            'nuevosAlumnosMes',
            'membresiasPorVencer',
            'membresiasVencidas',
            'seguimientosPendientes',
            'totalNoLeidas'
        ));
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
