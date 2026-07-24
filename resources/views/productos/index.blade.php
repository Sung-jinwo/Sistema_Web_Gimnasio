@extends('layouts.app')

@section('title', 'Productos')
@section('page-title', 'Productos')
@section('page-subtitle', 'Inventario de productos')

@section('content')
<div class="space-y-6">

    <div class="flex justify-end">
        <a href="{{ route('productos.create') }}" class="inline-flex items-center gap-2 bg-pink-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-pink-700 transition-colors text-sm">
            <i class="fas fa-plus"></i> Nuevo Producto
        </a>
    </div>

    <x-table :headers="['Codigo', 'Nombre', 'Marca', 'Precio', 'Stock', 'Categoria']">
        @forelse($productos ?? [] as $producto)
        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors {{ ($producto->prod_cantidad ?? 0) <= 0 ? 'bg-red-50' : '' }}">
            <td class="px-6 py-4 text-sm text-gray-700">{{ $producto->prod_codigo ?? '' }}</td>
            <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ $producto->prod_nombre ?? '' }}</td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ $producto->prod_marca ?? '' }}</td>
            <td class="px-6 py-4 text-sm font-semibold text-gray-900">S/ {{ number_format($producto->prod_precio ?? 0, 2) }}</td>
            <td class="px-6 py-4 text-sm">
                @if(($producto->prod_cantidad ?? 0) <= 0)
                    <x-badge variant="danger">Sin stock</x-badge>
                @elseif(($producto->prod_cantidad ?? 0) <= 5)
                    <x-badge variant="warning">{{ $producto->prod_cantidad }}</x-badge>
                @else
                    <x-badge variant="success">{{ $producto->prod_cantidad }}</x-badge>
                @endif
            </td>
            <td class="px-6 py-4 text-sm">
                <x-badge variant="info">{{ $producto->prod_categoria ?? '' }}</x-badge>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-box text-4xl text-gray-300 mb-3"></i>
                <p>No hay productos registrados</p>
            </td>
        </tr>
        @endforelse
    </x-table>

    @if(isset($productos) && $productos->hasPages())
    <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600">
            Mostrando {{ $productos->firstItem() ?? 0 }} a {{ $productos->lastItem() ?? 0 }} de {{ $productos->total() }} registros
        </div>
        <div>{{ $productos->links() }}</div>
    </div>
    @endif
</div>
@endsection
