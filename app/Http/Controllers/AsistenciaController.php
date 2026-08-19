<?php

namespace App\Http\Controllers;

use App\Http\Requests\AsistenciaRequest;
use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Pago;
use Illuminate\Http\Request;

class AsistenciaController extends Controller
{
    public function index(Request $request)
    {
        $query = Asistencia::with(['alumno', 'user', 'sede'])
            ->where('fksede', auth()->user()->fksede);

        if ($request->has('search') && $request->search) {
            $query->whereHas('alumno', function ($q) use ($request) {
                $q->where('alum_nombre', 'like', '%'.$request->search.'%')
                    ->orWhere('alum_apellido', 'like', '%'.$request->search.'%');
            });
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

    public function create()
    {
        $alumnos = Alumno::where('fksede', auth()->user()->fksede)
            ->orderBy('alum_nombre')
            ->get();

        return view('asistencia.create', compact('alumnos'));
    }

    public function store(AsistenciaRequest $request)
    {
        $fkalum = $request->validated()['fkalum'];

        $ultimoPago = Pago::where('fkalum', $fkalum)
            ->where('tipo_membresia', 'principal')
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $ultimoPago || $ultimoPago->pag_fin < now()->format('Y-m-d')) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'La membresia del alumno esta vencida'], 422);
            }

            return redirect()->back()
                ->withErrors(['error' => 'La membresia del alumno esta vencida'])
                ->withInput();
        }

        $data = $request->validated();
        $data['fkuser'] = auth()->id();
        $data['fksede'] = auth()->user()->fksede;
        $data['visi_fecha'] = now();

        $asistencia = Asistencia::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Asistencia registrada exitosamente',
                'asistencia' => $asistencia->load('alumno'),
            ]);
        }

        return redirect()->route('asistencia.index')
            ->with('success', 'Asistencia registrada exitosamente');
    }

    public function show(string $id)
    {
        $asistencia = Asistencia::with(['alumno', 'user', 'sede'])->findOrFail($id);

        return view('asistencia.show', compact('asistencia'));
    }

    public function destroy(Request $request, string $id)
    {
        try {
            $asistencia = Asistencia::findOrFail($id);
            $asistencia->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Asistencia eliminada exitosamente',
                ]);
            }

            return redirect()->route('asistencia.index')
                ->with('success', 'Asistencia eliminada exitosamente');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Asistencia no encontrada'], 404);
            }

            return redirect()->route('asistencia.index')
                ->withErrors(['error' => 'Asistencia no encontrada']);
        }
    }
}
