@extends('layouts.app')

@section('title', 'Usuarios')
@section('page-title', 'Usuarios')
@section('page-subtitle', 'Administracion de usuarios del sistema')

@section('content')
<div class="space-y-6">

    <div class="flex justify-end">
        <a href="{{ route('usuarios.create') }}" class="inline-flex items-center gap-2 bg-pink-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-pink-700 transition-colors text-sm">
            <i class="fas fa-plus"></i> Nuevo Usuario
        </a>
    </div>

    <x-table :headers="['Nombre', 'Email', 'Rol', 'Sede', 'Estado']">
        @forelse($usuarios ?? [] as $usuario)
        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ $usuario->name ?? '' }}</td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ $usuario->email ?? '' }}</td>
            <td class="px-6 py-4 text-sm">
                <x-badge variant="info">{{ $usuario->user_rol ?? '' }}</x-badge>
            </td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ $usuario->sede->sede_nombre ?? '' }}</td>
            <td class="px-6 py-4 text-sm">
                @if(($usuario->user_estado ?? '') === 'A')
                    <x-badge variant="success">Activo</x-badge>
                @else
                    <x-badge variant="danger">Inactivo</x-badge>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-user-shield text-4xl text-gray-300 mb-3"></i>
                <p>No hay usuarios registrados</p>
            </td>
        </tr>
        @endforelse
    </x-table>

    @if(isset($usuarios) && $usuarios->hasPages())
    <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600">
            Mostrando {{ $usuarios->firstItem() ?? 0 }} a {{ $usuarios->lastItem() ?? 0 }} de {{ $usuarios->total() }} registros
        </div>
        <div>{{ $usuarios->links() }}</div>
    </div>
    @endif
</div>
@endsection
