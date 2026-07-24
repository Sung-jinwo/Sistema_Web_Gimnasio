<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('sede');

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('rol') && $request->rol !== '') {
            $query->where('rol', $request->rol);
        }

        if ($request->has('estado') && $request->estado !== '') {
            $query->where('estado', $request->estado);
        }

        $usuarios = $query->orderByDesc('updated_at')->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($usuarios);
        }

        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        return view('usuarios.create');
    }

    public function store(UsuarioRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $usuario = User::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'usuario' => $usuario,
            ]);
        }

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado exitosamente');
    }

    public function show(string $id)
    {
        $usuario = User::with('sede')->findOrFail($id);

        return view('usuarios.show', compact('usuario'));
    }

    public function edit(string $id)
    {
        $usuario = User::findOrFail($id);

        if (request()->expectsJson()) {
            return response()->json($usuario->makeHidden(['password', 'remember_token']));
        }

        return view('usuarios.edit', compact('usuario'));
    }

    public function update(UsuarioRequest $request, string $id)
    {
        try {
            $usuario = User::findOrFail($id);
            $data = $request->validated();

            if (! empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $usuario->update($data);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Usuario actualizado exitosamente',
                    'usuario' => $usuario->fresh(),
                ]);
            }

            return redirect()->route('usuarios.index')
                ->with('success', 'Usuario actualizado exitosamente');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            return redirect()->route('usuarios.index')
                ->withErrors(['error' => 'Usuario no encontrado']);
        }
    }

    public function destroy(Request $request, string $id)
    {
        try {
            if ((int) $id === auth()->id()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'No puede eliminar su propio usuario'], 422);
                }

                return redirect()->route('usuarios.index')
                    ->withErrors(['error' => 'No puede eliminar su propio usuario']);
            }

            $usuario = User::findOrFail($id);
            $usuario->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Usuario eliminado exitosamente',
                ]);
            }

            return redirect()->route('usuarios.index')
                ->with('success', 'Usuario eliminado exitosamente');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            return redirect()->route('usuarios.index')
                ->withErrors(['error' => 'Usuario no encontrado']);
        }
    }
}
