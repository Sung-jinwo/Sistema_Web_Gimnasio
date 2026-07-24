<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Pago;

class DashboardController extends Controller
{
    public function index()
    {
        $totalAlumnos = Alumno::where('alum_estado', true)->count();

        $ingresosHoy = Pago::whereDate('created_at', today())
            ->where('estado_pago', 'completo')
            ->sum('pag_monto');

        $ingresosMes = Pago::whereMonth('created_at', now()->month)
            ->where('estado_pago', 'completo')
            ->sum('pag_monto');

        $asistenciasHoy = Asistencia::whereDate('visi_fecha', today())->count();

        $nuevosAlumnosMes = Alumno::whereMonth('created_at', now()->month)->count();

        $membresiasPorVencer = Pago::porVencer(5)->count();

        return view('dashboard.index', compact(
            'totalAlumnos',
            'ingresosHoy',
            'ingresosMes',
            'asistenciasHoy',
            'nuevosAlumnosMes',
            'membresiasPorVencer'
        ));
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
