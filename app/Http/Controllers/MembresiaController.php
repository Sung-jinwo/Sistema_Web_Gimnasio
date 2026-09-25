<?php

namespace App\Http\Controllers;

use App\Http\Requests\MembresiaRequest;
use App\Models\Alumno;
use App\Models\Membresia;
use App\Models\MembresiaAlumno;
use Illuminate\Http\Request;

class MembresiaController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Membresia::class);

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
        $this->authorize('create', Membresia::class);

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
        $this->authorize('update', $membresia);

        if (request()->expectsJson()) {
            return response()->json($membresia);
        }

        return redirect()->route('membresias.index');
    }

    public function update(MembresiaRequest $request, $id)
    {
        $membresia = Membresia::findOrFail($id);
        $this->authorize('update', $membresia);
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
        $this->authorize('delete', $membresia);
        $membresia->estado = $membresia->estado === 'A' ? 'I' : 'A';
        $membresia->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $membresia->estado === 'A' ? 'Membresía activada exitosamente' : 'Membresía desactivada exitosamente',
            ]);
        }

        return redirect()->route('membresias.index')
            ->with('success', $membresia->estado === 'A' ? 'Membresía activada exitosamente' : 'Membresía desactivada exitosamente');
    }

    public function historial($alumnoId)
    {
        $alumno = Alumno::findOrFail($alumnoId);
        $this->authorize('view', $alumno);

        $membresias = MembresiaAlumno::with('membresia')
            ->where('fkalumno', $alumnoId)
            ->orderByDesc('fecha_inicio')
            ->paginate(10);

        if (request()->expectsJson()) {
            return response()->json($membresias);
        }

        return view('membresias.historial', compact('alumno', 'membresias'));
    }
}
