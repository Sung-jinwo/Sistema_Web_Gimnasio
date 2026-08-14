@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reporte de Productos</h1>
            <p class="text-gray-600">Inventario y stock de productos</p>
        </div>
        <a href="{{ route('reportes.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
            <i class="fas fa-arrow-left mr-2"></i> Volver
        </a>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form method="GET" action="{{ route('reportes.productos') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @if(auth()->user()->hasRole('Administrador'))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sede</label>
                <select name="sede" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Todas</option>
                    @foreach($sedes as $sede)
                        <option value="{{ $sede->id_sede }}" {{ request('sede') == $sede->id_sede ? 'selected' : '' }}>{{ $sede->sede_nombre }}</option>
                    @endforeach
                </select>
            </div>
            @else
                <input type="hidden" name="sede" value="{{ auth()->user()->fksede }}">
            @endif
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                <select name="categoria" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Todas</option>
                    @foreach($categorias as $categoria)
                        <option value="{{ $categoria->id_categoria }}" {{ request('categoria') == $categoria->id_categoria ? 'selected' : '' }}>{{ $categoria->cat_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
            </div>
        </form>
    </div>

    {{-- Resumen --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Total Productos</p>
            <p class="text-3xl font-bold text-green-600">{{ $data['productos']->count() }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Total Stock</p>
            <p class="text-3xl font-bold text-blue-600">{{ $data['total_stock'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Valor Total</p>
            <p class="text-3xl font-bold text-purple-600">S/ {{ number_format($data['valor_total'], 2) }}</p>
        </div>
    </div>

    @if($data['stock_critico'] > 0)
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
            <p class="text-red-700"><strong>{{ $data['stock_critico'] }}</strong> producto(s) con stock crítico (por debajo del mínimo)</p>
        </div>
    </div>
    @endif

    {{-- Tabla --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Precio</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stock</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Mínimo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sede</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($data['productos'] as $producto)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $producto->prod_codigo ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $producto->prod_nombre }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $producto->categoria->cat_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right">S/ {{ number_format($producto->prod_precio, 2) }}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">{{ $producto->prod_cantidad }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 text-right">{{ $producto->prod_stock_minimo }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $producto->sede->sede_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($producto->prod_cantidad <= 0)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Sin Stock</span>
                            @elseif($producto->prod_cantidad <= $producto->prod_stock_minimo)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Stock Crítico</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Disponible</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">No se encontraron productos.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
