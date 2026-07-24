<?php

namespace App\Http\Controllers;

use App\Http\Requests\GastoRequest;
use App\Models\CategoriaGasto;
use App\Models\Gasto;
use Illuminate\Http\Request;

class GastoController extends Controller
{
    public function index(Request $request)
    {
        $query = Gasto::with(['categoria', 'user', 'sede'])
            ->where('fksede', auth()->user()->fksede);

        if ($request->has('fkcategoria') && $request->fkcategoria) {
            $query->where('fkcategoria', $request->fkcategoria);
        }

        if ($request->has('fecha_inicio') && $request->fecha_inicio) {
            $query->whereDate('gas_fecha', '>=', $request->fecha_inicio);
        }

        if ($request->has('fecha_fin') && $request->fecha_fin) {
            $query->whereDate('gas_fecha', '<=', $request->fecha_fin);
        }

        $gastos = $query->orderByDesc('gas_fecha')->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($gastos);
        }

        return view('gastos.index', compact('gastos'));
    }

    public function create()
    {
        $categorias = CategoriaGasto::all();

        return view('gastos.create', compact('categorias'));
    }

    public function store(GastoRequest $request)
    {
        $data = $request->validated();
        $data['fkuser'] = auth()->id();
        $data['fksede'] = auth()->user()->fksede;

        $gasto = Gasto::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Gasto registrado exitosamente',
                'gasto' => $gasto,
            ]);
        }

        return redirect()->route('gastos.index')
            ->with('success', 'Gasto registrado exitosamente');
    }

    public function show(string $id)
    {
        $gasto = Gasto::with(['categoria', 'user', 'sede'])->findOrFail($id);

        return view('gastos.show', compact('gasto'));
    }

    public function edit(string $id)
    {
        $gasto = Gasto::findOrFail($id);
        $categorias = CategoriaGasto::all();

        if (request()->expectsJson()) {
            return response()->json($gasto);
        }

        return view('gastos.edit', compact('gasto', 'categorias'));
    }

    public function update(GastoRequest $request, string $id)
    {
        try {
            $gasto = Gasto::findOrFail($id);
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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Gasto no encontrado'], 404);
            }

            return redirect()->route('gastos.index')
                ->withErrors(['error' => 'Gasto no encontrado']);
        }
    }

    public function destroy(Request $request, string $id)
    {
        try {
            $gasto = Gasto::findOrFail($id);
            $gasto->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Gasto eliminado exitosamente',
                ]);
            }

            return redirect()->route('gastos.index')
                ->with('success', 'Gasto eliminado exitosamente');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Gasto no encontrado'], 404);
            }

            return redirect()->route('gastos.index')
                ->withErrors(['error' => 'Gasto no encontrado']);
        }
    }
}
