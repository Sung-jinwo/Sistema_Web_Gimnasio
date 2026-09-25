<?php

namespace App\Http\Controllers;

use App\Exports\ReporteMensualExport;
use App\Models\Alumno;
use App\Models\Sede;
use App\Models\User;
use App\Services\ReporteMensualService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReporteAlumnoController extends Controller
{
    public function __construct(private readonly ReporteMensualService $reportes) {}

    /**
     * Tabla previa del reporte mensual con exactamente las columnas pedidas.
     */
    public function index(Request $request)
    {
        $this->authorize('reportar', Alumno::class);

        $filtros = $this->reportes->validar($request->all());
        $query = $this->reportes->construirQuery(auth()->user(), $filtros);
        $registros = $query->paginate(15)->withQueryString();

        $filas = $registros->getCollection()
            ->map(fn ($m) => $this->reportes->mapearFila($m, $filtros['columnas']));

        $registros->setCollection($filas);

        return view('alumnos.reporte', [
            'registros' => $registros,
            'filtros' => $filtros,
            'encabezados' => $this->reportes->encabezados($filtros['columnas']),
            'esAdmin' => auth()->user()->hasRole('Administrador'),
            'sedes' => auth()->user()->hasRole('Administrador')
                ? Sede::where('sede_estado', true)->orderBy('sede_nombre')->get(['id_sede', 'sede_nombre'])
                : collect(),
            'registradores' => auth()->user()->hasRole('Administrador')
                ? User::where('estado', true)->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    /**
     * Exporta TODOS los resultados con los mismos filtros, orden y columnas.
     */
    public function exportar(Request $request)
    {
        $this->authorize('reportar', Alumno::class);

        $filtros = $this->reportes->validar($request->all());
        $filas = $this->reportes->construirQuery(auth()->user(), $filtros)
            ->get()
            ->map(fn ($m) => array_values($this->reportes->mapearFila($m, $filtros['columnas'])))
            ->all();

        $nombre = sprintf('reporte_mensual_alumnos_%04d-%02d.xlsx', $filtros['anio'], $filtros['mes']);

        return Excel::download(
            new ReporteMensualExport($filas, $this->reportes->encabezados($filtros['columnas'])),
            $nombre
        );
    }
}
