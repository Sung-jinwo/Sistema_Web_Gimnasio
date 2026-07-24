<?php

namespace App\Http\Controllers;

use App\Http\Requests\VentaRequest;
use App\Models\Alumno;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Request;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $query = Venta::with(['alumno', 'user', 'sede', 'metodo', 'producto'])
            ->where('fksede', auth()->user()->fksede);

        if ($request->has('search') && $request->search) {
            $query->whereHas('alumno', function ($q) use ($request) {
                $q->where('alum_nombre', 'like', '%'.$request->search.'%')
                    ->orWhere('alum_apellido', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('estado_venta') && $request->estado_venta) {
            $query->where('estado_venta', $request->estado_venta);
        }

        $ventas = $query->orderByDesc('updated_at')->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($ventas);
        }

        return view('ventas.index', compact('ventas'));
    }

    public function create()
    {
        $alumnos = Alumno::where('fksede', auth()->user()->fksede)
            ->orderBy('alum_nombre')
            ->get();
        $productos = Producto::where('fksede', auth()->user()->fksede)->get();
        $metodos = MetodoPago::all();

        return view('ventas.create', compact('alumnos', 'productos', 'metodos'));
    }

    public function store(VentaRequest $request)
    {
        $data = $request->validated();
        $data['fkusers'] = auth()->id();
        $data['fksede'] = auth()->user()->fksede;

        $venta = Venta::create($data);

        if ($venta->estado_venta === 'completado' && $venta->fkproducto) {
            $producto = Producto::find($venta->fkproducto);
            if ($producto && $producto->prod_cantidad > 0) {
                $producto->prod_cantidad -= 1;
                $producto->save();
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Venta registrada exitosamente',
                'venta' => $venta->load(['alumno', 'metodo', 'producto']),
            ]);
        }

        return redirect()->route('ventas.index')
            ->with('success', 'Venta registrada exitosamente');
    }

    public function show(string $id)
    {
        $venta = Venta::with(['alumno', 'user', 'sede', 'metodo', 'producto', 'detalles'])->findOrFail($id);

        return view('ventas.show', compact('venta'));
    }

    public function edit(string $id)
    {
        $venta = Venta::findOrFail($id);
        $alumnos = Alumno::where('fksede', auth()->user()->fksede)
            ->orderBy('alum_nombre')
            ->get();
        $productos = Producto::where('fksede', auth()->user()->fksede)->get();
        $metodos = MetodoPago::all();

        if (request()->expectsJson()) {
            return response()->json($venta);
        }

        return view('ventas.edit', compact('venta', 'alumnos', 'productos', 'metodos'));
    }

    public function update(VentaRequest $request, string $id)
    {
        try {
            $venta = Venta::findOrFail($id);
            $data = $request->validated();

            if (! isset($data['fkusers'])) {
                $data['fkusers'] = $venta->fkusers ?? auth()->id();
            }

            $venta->update($data);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Venta actualizada exitosamente',
                    'venta' => $venta->fresh()->load(['alumno', 'metodo', 'producto']),
                ]);
            }

            return redirect()->route('ventas.index')
                ->with('success', 'Venta actualizada exitosamente');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Venta no encontrada'], 404);
            }

            return redirect()->route('ventas.index')
                ->withErrors(['error' => 'Venta no encontrada']);
        }
    }

    public function destroy(Request $request, string $id)
    {
        try {
            $venta = Venta::findOrFail($id);
            $venta->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Venta eliminada exitosamente',
                ]);
            }

            return redirect()->route('ventas.index')
                ->with('success', 'Venta eliminada exitosamente');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Venta no encontrada'], 404);
            }

            return redirect()->route('ventas.index')
                ->withErrors(['error' => 'Venta no encontrada']);
        }
    }

    public function reservados(Request $request)
    {
        $ventas = Venta::with(['alumno', 'user', 'sede', 'metodo', 'producto'])
            ->where('fksede', auth()->user()->fksede)
            ->where('estado_venta', 'reservado')
            ->orderByDesc('updated_at')
            ->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($ventas);
        }

        return view('ventas.reservados', compact('ventas'));
    }
}
