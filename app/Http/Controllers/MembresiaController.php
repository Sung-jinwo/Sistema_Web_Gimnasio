<?php

namespace App\Http\Controllers;

use App\Http\Requests\MembresiaRequest;
use App\Models\Membresia;
use Illuminate\Http\Request;

class MembresiaController extends Controller
{
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

    public function create()
    {
        return view('membresias.create');
    }

    public function store(MembresiaRequest $request)
    {
        $membresia = Membresia::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Membresía creada exitosamente',
                'membresia' => $membresia,
            ]);
        }

        return redirect()->route('membresias.index')
            ->with('success', 'Membresía creada exitosamente');
    }

    public function show(string $id)
    {
        $membresia = Membresia::findOrFail($id);

        return view('membresias.show', compact('membresia'));
    }

    public function edit(string $id)
    {
        $membresia = Membresia::findOrFail($id);

        if (request()->expectsJson()) {
            return response()->json($membresia);
        }

        return view('membresias.edit', compact('membresia'));
    }

    public function update(MembresiaRequest $request, string $id)
    {
        try {
            $membresia = Membresia::findOrFail($id);
            $membresia->update($request->validated());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Membresía actualizada exitosamente',
                    'membresia' => $membresia->fresh(),
                ]);
            }

            return redirect()->route('membresias.index')
                ->with('success', 'Membresía actualizada exitosamente');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Membresía no encontrada'], 404);
            }

            return redirect()->route('membresias.index')
                ->withErrors(['error' => 'Membresía no encontrada']);
        }
    }

    public function destroy(Request $request, string $id)
    {
        try {
            $membresia = Membresia::findOrFail($id);
            $membresia->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Membresía eliminada exitosamente',
                ]);
            }

            return redirect()->route('membresias.index')
                ->with('success', 'Membresía eliminada exitosamente');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Membresía no encontrada'], 404);
            }

            return redirect()->route('membresias.index')
                ->withErrors(['error' => 'Membresía no encontrada']);
        }
    }
}
