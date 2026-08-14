<?php

namespace App\Http\Controllers;

use App\Http\Requests\MembresiaRequest;
use App\Models\Alumno;
use App\Models\Membresia;
use App\Models\MembresiaAlumno;
use App\Services\MembresiaService;
use Illuminate\Http\Request;

class MembresiaController extends Controller
{
    protected MembresiaService $membresiaService;

    public function __construct(MembresiaService $membresiaService)
    {
        $this->membresiaService = $membresiaService;
    }

    public function index(Request $request)
    {
        $query = Membresia::query();

        if ($request->has('mem_categoria') && $request->mem_categoria) {
            $query->where('mem_categoria', $request->mem_categoria);
        }

        if ($request->has('estado') && $request->estado !== '') {
            $query->where('estado', $request->estado);
        }

        $membresias = $query->orderByDesc('updated_at')->paginate(10);

        if ($request->expectsJson()) {
            return response()->json($membresias);
        }

        return view('membresias.index', compact('membresias'));
    }

    public function store(MembresiaRequest $request)
    {
        $data = $request->validated();
        $data['comision'] = $data['comision'] ?? 0;
        $data['modalidad'] = $data['modalidad'] ?? 'por_meses';
        $data['estado'] = $data['estado'] ?? 'A';

        $membresia = Membresia::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Membresía creada exitosamente',
                'membresia' => $membresia,
            ], 201);
        }

        return redirect()->route('membresias.index')
            ->with('success', 'Membresía creada exitosamente');
    }

    public function edit($id)
    {
        $membresia = Membresia::findOrFail($id);

        if (request()->expectsJson()) {
            return response()->json($membresia);
        }

        return view('membresias.edit', compact('membresia'));
    }

    public function update(MembresiaRequest $request, $id)
    {
        $membresia = Membresia::findOrFail($id);
        $data = $request->validated();

        $membresia->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Membresía actualizada exitosamente',
                'membresia' => $membresia->fresh(),
            ]);
        }

        return redirect()->route('membresias.index')
            ->with('success', 'Membresía actualizada exitosamente');
    }

    public function destroy(Request $request, $id)
    {
        $membresia = Membresia::findOrFail($id);
        $membresia->estado = 'I';
        $membresia->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Membresía desactivada exitosamente',
            ]);
        }

        return redirect()->route('membresias.index')
            ->with('success', 'Membresía desactivada exitosamente');
    }

    public function asignar(Request $request, $alumnoId)
    {
        $alumno = Alumno::findOrFail($alumnoId);

        $request->validate([
            'fkmem' => 'required|exists:membresias,id_mem',
            'modalidad' => 'required|in:por_meses,por_fechas',
            'fecha_inicio' => 'required_if:modalidad,por_meses|nullable|date',
            'fecha_fin' => 'required_if:modalidad,por_fechas|nullable|date|after_or_equal:fecha_inicio',
        ]);

        $membresiaAlumno = $this->membresiaService->asignarMembresia(
            $alumnoId,
            $request->fkmem,
            $request->modalidad,
            $request->fecha_inicio,
            $request->fecha_fin
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Membresía asignada exitosamente',
                'data' => $membresiaAlumno->load('membresia'),
            ], 201);
        }

        return redirect()->route('alumnos.show', $alumnoId)
            ->with('success', 'Membresía asignada exitosamente');
    }

    public function historial($alumnoId)
    {
        $alumno = Alumno::findOrFail($alumnoId);
        $membresias = MembresiaAlumno::with('membresia')
            ->where('fkalumno', $alumnoId)
            ->orderByDesc('fecha_inicio')
            ->paginate(10);

        if (request()->expectsJson()) {
            return response()->json($membresias);
        }

        return view('membresias.historial', compact('alumno', 'membresias'));
    }

    public function renovar(Request $request, $membresiaAlumnoId)
    {
        $membresiaAlumno = MembresiaAlumno::with('membresia')->findOrFail($membresiaAlumnoId);

        $request->validate([
            'fecha_inicio' => 'required|date',
        ]);

        $nuevaMembresia = $this->membresiaService->asignarMembresia(
            $membresiaAlumno->fkalumno,
            $membresiaAlumno->fkmem,
            $membresiaAlumno->modalidad,
            $request->fecha_inicio,
            null
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Membresía renovada exitosamente',
                'data' => $nuevaMembresia->load('membresia'),
            ], 201);
        }

        return redirect()->route('alumnos.show', $membresiaAlumno->fkalumno)
            ->with('success', 'Membresía renovada exitosamente');
    }
}
