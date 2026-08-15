<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasRole(['Administrador', 'Local']), 403);

        return view('categorias.index', ['categorias' => Categoria::withCount('productos')->orderBy('cat_nombre')->paginate(15)]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole('Administrador'), 403);
        $data = $request->validate(['cat_nombre' => 'required|string|max:50|unique:categorias,cat_nombre']);
        $data['cat_estado'] = true;
        Categoria::create($data);

        return back()->with('success', 'Categoría creada exitosamente.');
    }

    public function update(Request $request, Categoria $categoria)
    {
        abort_unless(auth()->user()->hasRole('Administrador'), 403);
        $categoria->update($request->validate([
            'cat_nombre' => ['required', 'string', 'max:50', Rule::unique('categorias', 'cat_nombre')->ignore($categoria->id_categoria, 'id_categoria')],
        ]));

        return back()->with('success', 'Categoría actualizada exitosamente.');
    }

    public function toggle(Categoria $categoria)
    {
        abort_unless(auth()->user()->hasRole('Administrador'), 403);
        if ($categoria->cat_estado && $categoria->productos()->where('prod_estado', true)->exists()) {
            return back()->withErrors(['categoria' => 'No puede desactivar una categoría que tiene productos activos.']);
        }
        $categoria->update(['cat_estado' => ! $categoria->cat_estado]);

        return back()->with('success', $categoria->cat_estado ? 'Categoría activada.' : 'Categoría desactivada.');
    }
}
