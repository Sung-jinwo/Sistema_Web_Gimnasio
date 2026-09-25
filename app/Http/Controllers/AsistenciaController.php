<?php

namespace App\Http\Controllers;

use App\Http\Requests\AsistenciaRequest;
use App\Models\Asistencia;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AsistenciaController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    public function index(Request $request)
    {
        $query = Asistencia::with(['alumno', 'user', 'sede'])
            ->where('fksede', auth()->user()->fksede);

        if ($request->has('search') && $request->search) {
            $query->whereHas('alumno', function ($q) use ($request) {
                $q->where('alum_codigo', 'like', '%'.$request->search.'%')
                    ->orWhere('alum_numDoc', 'like', '%'.$request->search.'%');
            });
        }

        if (in_array($request->tipo_ingreso, ['codigo', 'dni', 'qr', 'huella'], true)) {
            $query->where('tipo_ingreso', $request->tipo_ingreso);
        }

        if ($request->has('fecha') && $request->fecha) {
            $query->whereDate('visi_fecha', $request->fecha);
        }

        $asistencias = $query->orderByDesc('visi_fecha')->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($asistencias);
        }

        return view('asistencia.index', compact('asistencias'));
    }

    public function store(AsistenciaRequest $request)
    {
        $data = $request->validated();
        $alumno = $this->attendanceService->buscarAlumno($data['codigo_documento'], $data['tipo_ingreso']);

        if (! $alumno) {
            return redirect()->back()
                ->withErrors(['codigo_documento' => 'No se encontró un alumno con los datos ingresados.'])
                ->withInput();
        }

        if (! $this->attendanceService->validarAlumnoActivo($alumno)) {
            return redirect()->back()
                ->withErrors(['codigo_documento' => 'El alumno está inactivo.'])
                ->withInput();
        }

        if (! $this->attendanceService->validarMembresiaVigente($alumno)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'El alumno no tiene una membresía vigente'], 422);
            }

            return redirect()->back()
                ->withErrors(['codigo_documento' => 'El alumno no tiene una membresía vigente.'])
                ->withInput();
        }

        if ($congelado = $this->attendanceService->congelamientoBloqueante($alumno)) {
            $mensaje = 'Su membresía está congelada hasta el '.$congelado->fecha_fin->format('d/m/Y').'. No se permite el ingreso durante el congelamiento.';

            if ($request->expectsJson()) {
                return response()->json(['error' => $mensaje], 422);
            }

            return redirect()->back()
                ->withErrors(['codigo_documento' => $mensaje])
                ->withInput();
        }

        $asistencia = $this->attendanceService->registrarAsistencia(
            $alumno,
            auth()->user()->fksede,
            $data['tipo_ingreso'],
            auth()->id()
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Asistencia registrada exitosamente',
                'asistencia' => $asistencia->load('alumno'),
            ]);
        }

        return redirect()->route('asistencias.index')
            ->with('success', 'Asistencia registrada exitosamente');
    }
}
