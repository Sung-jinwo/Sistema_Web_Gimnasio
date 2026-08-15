<?php

namespace App\Http\Controllers;

use App\Http\Requests\GastoRequest;
use App\Models\CategoriaGasto;
use App\Models\Gasto;
use App\Services\AuditService;
use Illuminate\Http\Request;

class GastoController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Gasto::class);

        $query = Gasto::with(['categoria', 'user', 'sede', 'aprobadoPor']);

        if (! auth()->user()->hasRole('Administrador')) {
            $query->where('fksede', auth()->user()->fksede);
        }

        if ($request->has('fkcategoria') && $request->fkcategoria) {
            $query->where('fkcategoria', $request->fkcategoria);
        }

        if ($request->has('estado') && $request->estado) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_inicio') && $request->fecha_inicio) {
            $query->whereDate('gas_fecha', '>=', $request->fecha_inicio);
        }

        if ($request->has('fecha_fin') && $request->fecha_fin) {
            $query->whereDate('gas_fecha', '<=', $request->fecha_fin);
        }

        $gastos = $query->orderByDesc('gas_fecha')->paginate(15);
        $categorias = CategoriaGasto::all();

        if ($request->expectsJson()) {
            return response()->json($gastos);
        }

        return view('gastos.index', compact('gastos', 'categorias'));
    }

    public function store(GastoRequest $request)
    {
        $this->authorize('create', Gasto::class);

        $data = $request->validated();
        $data['fkuser'] = auth()->id();
        $data['fksede'] = auth()->user()->fksede;
        $data['estado'] = 'pendiente';

        $gasto = Gasto::create($data);

        $this->auditService->registrarCreacion(
            'gastos',
            'Gasto',
            $gasto->id_gasto,
            $gasto->toArray()
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Gasto registrado exitosamente',
                'gasto' => $gasto,
            ], 201);
        }

        return redirect()->route('gastos.index')
            ->with('success', 'Gasto registrado exitosamente');
    }

    public function edit($id)
    {
        $gasto = Gasto::findOrFail($id);
        $this->authorize('update', $gasto);

        $categorias = CategoriaGasto::all();

        if (request()->expectsJson()) {
            return response()->json($gasto);
        }

        return view('gastos.edit', compact('gasto', 'categorias'));
    }

    public function update(GastoRequest $request, $id)
    {
        $gasto = Gasto::findOrFail($id);
        $this->authorize('update', $gasto);

        $data = $request->validated();

        if (! isset($data['fkuser'])) {
            $data['fkuser'] = $gasto->fkuser ?? auth()->id();
        }

        $gasto->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Gasto actualizado exitosamente',
                'gasto' => $gasto->fresh(),
            ]);
        }

        return redirect()->route('gastos.index')
            ->with('success', 'Gasto actualizado exitosamente');
    }

    public function destroy(Request $request, $id)
    {
        $gasto = Gasto::findOrFail($id);
        $this->authorize('delete', $gasto);

        $gasto->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Gasto eliminado exitosamente',
            ]);
        }

        return redirect()->route('gastos.index')
            ->with('success', 'Gasto eliminado exitosamente');
    }

    public function aprobar(Request $request, $id)
    {
        $gasto = Gasto::findOrFail($id);
        $this->authorize('aprobar', $gasto);

        $gasto->update([
            'estado' => 'aprobado',
            'aprobado_por' => auth()->id(),
            'fecha_aprobacion' => now(),
        ]);

        $this->auditService->registrarAprobacion(
            'gastos',
            'Gasto',
            $gasto->id_gasto,
            $gasto->fresh()->toArray()
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Gasto aprobado exitosamente',
                'gasto' => $gasto->fresh(),
            ]);
        }

        return redirect()->route('gastos.index')
            ->with('success', 'Gasto aprobado exitosamente');
    }

    public function rechazar(Request $request, $id)
    {
        $gasto = Gasto::findOrFail($id);
        $this->authorize('rechazar', $gasto);

        $request->validate([
            'motivo_rechazo' => 'required|string|max:500',
        ], [
            'motivo_rechazo.required' => 'Debe ingresar un motivo para rechazar el gasto.',
            'motivo_rechazo.string' => 'El motivo debe ser texto.',
            'motivo_rechazo.max' => 'El motivo no puede superar los 500 caracteres.',
        ]);

        $gasto->update([
            'estado' => 'rechazado',
            'aprobado_por' => auth()->id(),
            'fecha_aprobacion' => now(),
            'motivo_rechazo' => $request->motivo_rechazo,
        ]);

        $this->auditService->registrarRechazo(
            'gastos',
            'Gasto',
            $gasto->id_gasto,
            $gasto->fresh()->toArray()
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Gasto rechazado exitosamente',
                'gasto' => $gasto->fresh(),
            ]);
        }

        return redirect()->route('gastos.index')
            ->with('success', 'Gasto rechazado exitosamente');
    }
}
