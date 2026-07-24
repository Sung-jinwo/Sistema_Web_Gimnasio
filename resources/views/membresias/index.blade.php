@extends('layouts.app')

@section('title', 'Membresias')
@section('page-title', 'Membresias')
@section('page-subtitle', 'Planes y membresias del gimnasio')

@section('content')
<div class="space-y-6">

    <div class="flex justify-end">
        <a href="{{ route('membresias.create') }}" class="inline-flex items-center gap-2 bg-pink-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-pink-700 transition-colors text-sm">
            <i class="fas fa-plus"></i> Nueva Membresia
        </a>
    </div>

    <x-table :headers="['Nombre', 'Tipo', 'Precio', 'Duracion', 'Categoria', 'Estado']">
        @forelse($membresias ?? [] as $membresia)
        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ $membresia->memb_nombre ?? '' }}</td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ $membresia->memb_tipo ?? '' }}</td>
            <td class="px-6 py-4 text-sm font-semibold text-gray-900">S/ {{ number_format($membresia->memb_precio ?? 0, 2) }}</td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ $membresia->memb_duracion ?? '' }} dias</td>
            <td class="px-6 py-4 text-sm">
                <x-badge variant="info">{{ $membresia->memb_categoria ?? '' }}</x-badge>
            </td>
            <td class="px-6 py-4 text-sm">
                @if(($membresia->memb_estado ?? '') === 'A')
                    <x-badge variant="success">Activo</x-badge>
                @else
                    <x-badge variant="danger">Inactivo</x-badge>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-id-card text-4xl text-gray-300 mb-3"></i>
                <p>No hay membresias registradas</p>
            </td>
        </tr>
        @endforelse
    </x-table>

    @if(isset($membresias) && $membresias->hasPages())
    <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600">
            Mostrando {{ $membresias->firstItem() ?? 0 }} a {{ $membresias->lastItem() ?? 0 }} de {{ $membresias->total() }} registros
        </div>
        <div>{{ $membresias->links() }}</div>
    </div>
    @endif
</div>
@endsection
