<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::with(['sede', 'roles']);

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('role') && $request->role !== '') {
            $query->role($request->role);
        }

        if ($request->has('estado') && $request->estado !== '') {
            $query->where('estado', $request->estado);
        }

        $usuarios = $query->orderByDesc('updated_at')->paginate(15);
        $roles = Role::orderBy('name')->pluck('name');

        if ($request->expectsJson()) {
            return response()->json($usuarios);
        }

        return view('usuarios.index', compact('usuarios', 'roles'));
    }

    public function store(UsuarioRequest $request)
    {
        $this->authorize('create', User::class);

        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $role = $data['role'] ?? 'Local';
        unset($data['role']);

        $usuario = User::create($data);
        $usuario->assignRole($role);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'usuario' => $usuario->load('roles'),
            ], 201);
        }

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado exitosamente');
    }

    public function edit(string $id)
    {
        $usuario = User::with('roles')->findOrFail($id);
        $this->authorize('update', $usuario);

        if (request()->expectsJson()) {
            return response()->json([
                'data' => $usuario->makeHidden(['password', 'remember_token']),
                'roles' => Role::orderBy('name')->pluck('name'),
            ]);
        }

        $roles = Role::orderBy('name')->pluck('name');

        return view('usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(UsuarioRequest $request, string $id)
    {
        $usuario = User::findOrFail($id);
        $this->authorize('update', $usuario);

        $data = $request->validated();
        $role = $data['role'] ?? null;
        unset($data['role']);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $usuario->update($data);

        if ($role) {
            $usuario->syncRoles([$role]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'usuario' => $usuario->fresh('roles'),
            ]);
        }

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente');
    }

    public function destroy(Request $request, string $id)
    {
        $usuario = User::findOrFail($id);
        $this->authorize('delete', $usuario);

        $usuario->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente',
            ]);
        }

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario eliminado exitosamente');
    }

    public function toggleEstado(Request $request, string $id)
    {
        $usuario = User::findOrFail($id);
        $this->authorize('toggleEstado', $usuario);

        $usuario->estado = ! $usuario->estado;
        $usuario->save();

        $mensaje = $usuario->estado ? 'Usuario activado.' : 'Usuario desactivado.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'usuario' => $usuario,
            ]);
        }

        return redirect()->route('usuarios.index')
            ->with('success', $mensaje);
    }
}
