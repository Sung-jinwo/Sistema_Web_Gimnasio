<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\CategoriaGasto;
use App\Models\Sede;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return view('reportes.index');
    }

    public function ventas(Request $request)
    {
        $this->authorizeReporte();

        $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'sede', 'empleado', 'tipo_venta']);
        $sedeId = $this->filtrarSede($request->input('sede'));

        $data = $this->reportService->reporteVentas(
            $request->input('fecha_inicio'),
            $request->input('fecha_fin'),
            $sedeId,
            $request->input('empleado'),
            $request->input('tipo_venta')
        );

        $sedes = $this->obtenerSedes();
        $empleados = $this->obtenerEmpleados($sedeId);

        if ($request->input('export') === 'excel') {
            return $this->exportarExcel('ventas', $data, $filtros);
        }

        if ($request->input('export') === 'pdf') {
            return $this->exportarPdf('ventas', $data, $filtros);
        }

        return view('reportes.ventas', compact('data', 'sedes', 'empleados', 'filtros'));
    }

    public function membresias(Request $request)
    {
        $this->authorizeReporte();

        $sedeId = $this->filtrarSede($request->input('sede'));

        $data = $this->reportService->reporteMembresias(
            $request->input('estado'),
            $sedeId
        );

        $sedes = $this->obtenerSedes();

        return view('reportes.membresias', compact('data', 'sedes'));
    }

    public function productos(Request $request)
    {
        $this->authorizeReporte();

        $sedeId = $this->filtrarSede($request->input('sede'));

        $data = $this->reportService->reporteProductos(
            $sedeId,
            $request->input('categoria')
        );

        $sedes = $this->obtenerSedes();
        $categorias = Categoria::orderBy('cat_nombre')->get();

        return view('reportes.productos', compact('data', 'sedes', 'categorias'));
    }

    public function comisiones(Request $request)
    {
        $this->authorizeReporte();

        $data = $this->reportService->reporteComisiones(
            $request->input('fecha_inicio'),
            $request->input('fecha_fin'),
            $request->input('empleado')
        );

        $empleados = $this->obtenerEmpleados();

        return view('reportes.comisiones', compact('data', 'empleados'));
    }

    public function gastos(Request $request)
    {
        $this->authorizeReporte();

        $sedeId = $this->filtrarSede($request->input('sede'));

        $data = $this->reportService->reporteGastos(
            $request->input('fecha_inicio'),
            $request->input('fecha_fin'),
            $sedeId,
            $request->input('categoria'),
            $request->input('estado')
        );

        $sedes = $this->obtenerSedes();
        $categorias = CategoriaGasto::orderBy('cat_nombre')->get();

        return view('reportes.gastos', compact('data', 'sedes', 'categorias'));
    }

    public function caja(Request $request)
    {
        $this->authorizeReporte();

        $sedeId = $this->filtrarSede($request->input('sede'));

        $data = $this->reportService->reporteCaja(
            $request->input('fecha_inicio'),
            $request->input('fecha_fin'),
            $sedeId,
            $request->input('empleado')
        );

        $sedes = $this->obtenerSedes();
        $empleados = $this->obtenerEmpleados($sedeId);

        return view('reportes.caja', compact('data', 'sedes', 'empleados'));
    }

    public function vencimientos(Request $request)
    {
        $this->authorizeReporte();

        $sedeId = $this->filtrarSede($request->input('sede'));

        $data = $this->reportService->reporteVencimientos(
            $request->input('mes'),
            $request->input('anio'),
            $sedeId,
            $request->input('estado')
        );

        $sedes = $this->obtenerSedes();

        return view('reportes.vencimientos', compact('data', 'sedes'));
    }

    protected function authorizeReporte(): void
    {
        $user = auth()->user();
        if (!$user->hasRole(['Administrador', 'Local'])) {
            abort(403, 'No tienes permiso para acceder a los reportes.');
        }
    }

    protected function filtrarSede(?int $sedeId): ?int
    {
        if (auth()->user()->hasRole('Administrador')) {
            return $sedeId;
        }

        return auth()->user()->fksede;
    }

    protected function obtenerSedes()
    {
        if (auth()->user()->hasRole('Administrador')) {
            return Sede::where('sede_estado', true)->orderBy('sede_nombre')->get();
        }

        return Sede::where('id_sede', auth()->user()->fksede)->get();
    }

    protected function obtenerEmpleados(?int $sedeId = null)
    {
        $query = User::where('estado', true);

        if ($sedeId) {
            $query->where('fksede', $sedeId);
        } elseif (!auth()->user()->hasRole('Administrador')) {
            $query->where('fksede', auth()->user()->fksede);
        }

        return $query->orderBy('name')->get();
    }

    protected function exportarExcel(string $tipo, array $data, array $filtros)
    {
        $className = 'App\\Exports\\' . ucfirst($tipo) . 'ReportExport';
        
        if (!class_exists($className)) {
            abort(404, 'Export no disponible para este reporte.');
        }

        $export = new $className($data, $filtros);
        $filename = $tipo . '_' . date('Y-m-d') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }

    protected function exportarPdf(string $tipo, array $data, array $filtros)
    {
        $viewName = 'reportes.pdf.' . $tipo;
        
        if (!view()->exists($viewName)) {
            abort(404, 'Vista PDF no disponible para este reporte.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($viewName, compact('data', 'filtros'));
        $filename = $tipo . '_' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}
