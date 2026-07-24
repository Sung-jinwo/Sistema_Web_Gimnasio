@extends('layouts.app')

@section('title', 'Ventas')
@section('page-title', 'Ventas')
@section('page-subtitle', 'Registro de ventas de productos')

@section('content')
<div class="space-y-6">

    <div class="flex justify-end">
        <a href="{{ route('ventas.create') }}" class="inline-flex items-center gap-2 bg-pink-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-pink-700 transition-colors text-sm">
            <i class="fas fa-plus"></i> Nueva Venta
        </a>
    </div>

    <x-table :headers="['Fecha', 'Alumno', 'Total', 'Metodo Pago', 'Estado']">
        @forelse($ventas ?? [] as $venta)
        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 text-sm text-gray-700">{{ $venta->vent_fecha ?? '' }}</td>
            <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ $venta->alumno->alum_nombre ?? '' }} {{ $venta->alumno->alum_apellido ?? '' }}</td>
            <td class="px-6 py-4 text-sm font-semibold text-gray-900">S/ {{ number_format($venta->vent_total ?? 0, 2) }}</td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ $venta->metodoPago->metpago_nombre ?? '' }}</td>
            <td class="px-6 py-4 text-sm">
                @if(($venta->vent_estado ?? '') === 'Completada')
                    <x-badge variant="success">Completada</x-badge>
                @elseif(($venta->vent_estado ?? '') === 'Reservada')
                    <x-badge variant="warning">Reservada</x-badge>
                @elseif(($venta->vent_estado ?? '') === 'Cancelada')
                    <x-badge variant="danger">Cancelada</x-badge>
                @else
                    <x-badge variant="default">{{ $venta->vent_estado ?? 'N/A' }}</x-badge>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-shopping-cart text-4xl text-gray-300 mb-3"></i>
                <p>No hay ventas registradas</p>
            </td>
        </tr>
        @endforelse
    </x-table>

    @if(isset($ventas) && $ventas->hasPages())
    <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600">
            Mostrando {{ $ventas->firstItem() ?? 0 }} a {{ $ventas->lastItem() ?? 0 }} de {{ $ventas->total() }} registros
        </div>
        <div>{{ $ventas->links() }}</div>
    </div>
    @endif
</div>
@endsection
