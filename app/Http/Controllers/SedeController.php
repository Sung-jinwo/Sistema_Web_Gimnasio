<?php

namespace App\Http\Controllers;

use App\Http\Requests\SedeRequest;
use App\Models\Sede;
use Illuminate\Http\Request;

class SedeController extends Controller
{
    public function index(Request $request)
    {
        $query = Sede::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('sede_nombre', 'like', "%{$search}%")
                ->orWhere('sede_direccion', 'like', "%{$search}%");
        }

        $sedes = $query->orderBy('sede_nombre')->paginate(10);

        if ($request->expectsJson()) {
            return response()->json($sedes);
        }

        return view('sedes.index', compact('sedes'));
    }

    public function store(SedeRequest $request)
    {
        $data = $request->validated();
        $data['sede_estado'] = $request->has('sede_estado') ? true : false;

        $sede = Sede::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sede creada exitosamente.',
                'data' => $sede,
            ], 201);
        }

        return redirect()->route('sedes.index')
            ->with('success', 'Sede creada exitosamente.');
    }

    public function edit($id)
    {
        $sede = Sede::findOrFail($id);

        if (request()->expectsJson()) {
            return response()->json($sede);
        }

        return view('sedes.edit', compact('sede'));
    }

    public function update(SedeRequest $request, $id)
    {
        $sede = Sede::findOrFail($id);
        $data = $request->validated();
        $data['sede_estado'] = $request->has('sede_estado') ? true : false;

        $sede->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sede actualizada exitosamente.',
                'data' => $sede,
            ]);
        }

        return redirect()->route('sedes.index')
            ->with('success', 'Sede actualizada exitosamente.');
    }

    public function toggleEstado(Request $request, $id)
    {
        $sede = Sede::findOrFail($id);
        $sede->sede_estado = !$sede->sede_estado;
        $sede->save();

        $mensaje = $sede->sede_estado ? 'Sede activada.' : 'Sede desactivada.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $mensaje,
                'data' => $sede,
            ]);
        }

        return redirect()->route('sedes.index')
            ->with('success', $mensaje);
    }
}
