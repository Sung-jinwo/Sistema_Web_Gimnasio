<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductoRequest;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $query = Producto::with(['categoria', 'user', 'sede'])
            ->where('fksede', auth()->user()->fksede);

        if ($request->has('search') && $request->search) {
            $query->where('prod_nombre', 'like', '%'.$request->search.'%');
        }

        if ($request->has('fkcategoria') && $request->fkcategoria) {
            $query->where('fkcategoria', $request->fkcategoria);
        }

        $productos = $query->orderByDesc('updated_at')->paginate(12);

        if ($request->expectsJson()) {
            return response()->json($productos);
        }

        return view('productos.index', compact('productos'));
    }

    public function create()
    {
        $categorias = Categoria::all();

        return view('productos.create', compact('categorias'));
    }

    public function store(ProductoRequest $request)
    {
        $data = $request->validated();
        $data['fkusers'] = auth()->id();
        $data['fksede'] = auth()->user()->fksede;

        $producto = Producto::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'producto' => $producto,
            ]);
        }

        return redirect()->route('productos.index')
            ->with('success', 'Producto creado exitosamente');
    }

    public function show(string $id)
    {
        $producto = Producto::with(['categoria', 'user', 'sede'])->findOrFail($id);

        return view('productos.show', compact('producto'));
    }

    public function edit(string $id)
    {
        $producto = Producto::findOrFail($id);
        $categorias = Categoria::all();

        if (request()->expectsJson()) {
            return response()->json($producto);
        }

        return view('productos.edit', compact('producto', 'categorias'));
    }

    public function update(ProductoRequest $request, string $id)
    {
        try {
            $producto = Producto::findOrFail($id);
            $data = $request->validated();

            if (! isset($data['fkusers'])) {
                $data['fkusers'] = $producto->fkusers ?? auth()->id();
            }

            $producto->update($data);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Producto actualizado exitosamente',
                    'producto' => $producto->fresh(),
                ]);
            }

            return redirect()->route('productos.index')
                ->with('success', 'Producto actualizado exitosamente');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Producto no encontrado'], 404);
            }

            return redirect()->route('productos.index')
                ->withErrors(['error' => 'Producto no encontrado']);
        }
    }

    public function destroy(Request $request, string $id)
    {
        try {
            $producto = Producto::findOrFail($id);
            $producto->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Producto eliminado exitosamente',
                ]);
            }

            return redirect()->route('productos.index')
                ->with('success', 'Producto eliminado exitosamente');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Producto no encontrado'], 404);
            }

            return redirect()->route('productos.index')
                ->withErrors(['error' => 'Producto no encontrado']);
        }
    }
}
