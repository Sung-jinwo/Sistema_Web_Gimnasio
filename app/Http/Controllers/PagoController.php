<?php

namespace App\Http\Controllers;

use App\Http\Requests\PagoRequest;
use App\Models\Alumno;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Pago;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    public function index(Request $request)
    {
        $query = Pago::with(['alumno', 'membresia', 'metodo', 'user', 'sede'])
            ->where('fksede', auth()->user()->fksede);

        if ($request->has('search') && $request->search) {
            $query->whereHas('alumno', function ($q) use ($request) {
                $q->where('alum_nombre', 'like', '%'.$request->search.'%')
                    ->orWhere('alum_apellido', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('estado_pago') && $request->estado_pago) {
            $query->where('estado_pago', $request->estado_pago);
        }

        $pagos = $query->orderByDesc('updated_at')->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($pagos);
        }

        return view('pagos.index', compact('pagos'));
    }

    public function create()
    {
        $alumnos = Alumno::where('fksede', auth()->user()->fksede)
            ->orderBy('alum_nombre')
            ->get();
        $membresias = Membresia::all();
        $metodos = MetodoPago::all();

        return view('pagos.create', compact('alumnos', 'membresias', 'metodos'));
    }

    public function store(PagoRequest $request)
    {
        $data = $request->validated();
        $data['fkuser'] = auth()->id();
        $data['fksede'] = auth()->user()->fksede;

        $pago = Pago::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pago registrado exitosamente',
                'pago' => $pago->load(['alumno', 'membresia', 'metodo']),
            ]);
        }

        return redirect()->route('pagos.index')
            ->with('success', 'Pago registrado exitosamente');
    }

    public function show(string $id)
    {
        $pago = Pago::with(['alumno', 'membresia', 'metodo', 'user', 'sede', 'detalles'])->findOrFail($id);

        return view('pagos.show', compact('pago'));
    }

    public function edit(string $id)
    {
        $pago = Pago::findOrFail($id);
        $alumnos = Alumno::where('fksede', auth()->user()->fksede)
            ->orderBy('alum_nombre')
            ->get();
        $membresias = Membresia::all();
        $metodos = MetodoPago::all();

        if (request()->expectsJson()) {
            return response()->json($pago);
        }

        return view('pagos.edit', compact('pago', 'alumnos', 'membresias', 'metodos'));
    }

    public function update(PagoRequest $request, string $id)
    {
        try {
            $pago = Pago::findOrFail($id);
            $data = $request->validated();

            if (! isset($data['fkuser'])) {
                $data['fkuser'] = $pago->fkuser ?? auth()->id();
            }

            $pago->update($data);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pago actualizado exitosamente',
                    'pago' => $pago->fresh()->load(['alumno', 'membresia', 'metodo']),
                ]);
            }

            return redirect()->route('pagos.index')
                ->with('success', 'Pago actualizado exitosamente');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Pago no encontrado'], 404);
            }

            return redirect()->route('pagos.index')
                ->withErrors(['error' => 'Pago no encontrado']);
        }
    }

    public function destroy(Request $request, string $id)
    {
        try {
            $pago = Pago::findOrFail($id);
            $pago->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pago eliminado exitosamente',
                ]);
            }

            return redirect()->route('pagos.index')
                ->with('success', 'Pago eliminado exitosamente');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Pago no encontrado'], 404);
            }

            return redirect()->route('pagos.index')
                ->withErrors(['error' => 'Pago no encontrado']);
        }
    }

    public function completos(Request $request)
    {
        $pagos = Pago::with(['alumno', 'membresia', 'metodo', 'user', 'sede'])
            ->where('fksede', auth()->user()->fksede)
            ->where('estado_pago', 'completo')
            ->orderByDesc('updated_at')
            ->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($pagos);
        }

        return view('pagos.completos', compact('pagos'));
    }

    public function incompletos(Request $request)
    {
        $pagos = Pago::with(['alumno', 'membresia', 'metodo', 'user', 'sede'])
            ->where('fksede', auth()->user()->fksede)
            ->whereIn('estado_pago', ['incompleto', 'reservado'])
            ->orderByDesc('updated_at')
            ->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($pagos);
        }

        return view('pagos.incompletos', compact('pagos'));
    }
}
