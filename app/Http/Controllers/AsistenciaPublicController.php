<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AsistenciaPublicController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index()
    {
        $sedes = $this->attendanceService->obtenerSedesActivas();

        return view('asistencia.publica', compact('sedes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo_documento' => 'required|string|max:20',
            'sede_id' => 'required|exists:sedes,id_sede',
        ], [
            'codigo_documento.required' => 'Ingrese el DNI o código del alumno',
            'codigo_documento.max' => 'El código no puede exceder 20 caracteres',
            'sede_id.required' => 'Seleccione una sede',
            'sede_id.exists' => 'La sede seleccionada no es válida',
        ]);

        $resultado = $this->attendanceService->procesarRegistroPublico(
            $request->codigo_documento,
            $request->sede_id
        );

        if ($request->expectsJson()) {
            return response()->json($resultado, $resultado['success'] ? 200 : 422);
        }

        $sedes = $this->attendanceService->obtenerSedesActivas();

        return view('asistencia.publica', compact('sedes'))
            ->with('resultado', $resultado);
    }
}
