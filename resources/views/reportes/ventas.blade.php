@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reporte de Ventas</h1>
            <p class="text-gray-600">Análisis de ventas por período</p>
        </div>
        <a href="{{ route('reportes.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
            <i class="fas fa-arrow-left mr-2"></i> Volver
        </a>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form method="GET" action="{{ route('reportes.ventas') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" value="{{ $filtros['fecha_inicio'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label>
                <input type="date" name="fecha_fin" value="{{ $filtros['fecha_fin'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>
            @if(auth()->user()->hasRole('Administrador'))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sede</label>
                <select name="sede" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Todas</option>
                    @foreach($sedes as $sede)
                        <option value="{{ $sede->id_sede }}" {{ ($filtros['sede'] ?? '') == $sede->id_sede ? 'selected' : '' }}>{{ $sede->sede_nombre }}</option>
                    @endforeach
                </select>
            </div>
            @else
                <input type="hidden" name="sede" value="{{ auth()->user()->fksede }}">
            @endif
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Venta</label>
                <select name="tipo_venta" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Todos</option>
                    <option value="producto" {{ ($filtros['tipo_venta'] ?? '') === 'producto' ? 'selected' : '' }}>Producto</option>
                    <option value="membresia" {{ ($filtros['tipo_venta'] ?? '') === 'membresia' ? 'selected' : '' }}>Membresía</option>
                    <option value="rapida" {{ ($filtros['tipo_venta'] ?? '') === 'rapida' ? 'selected' : '' }}>Rápida</option>
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
            <p class="text-sm text-gray-500">Total Ventas</p>
            <p class="text-3xl font-bold text-blue-600">S/ {{ number_format($data['total'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Cantidad</p>
            <p class="text-3xl font-bold text-gray-900">{{ $data['cantidad'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Promedio</p>
            <p class="text-3xl font-bold text-green-600">S/ {{ number_format($data['cantidad'] > 0 ? $data['total'] / $data['cantidad'] : 0, 2) }}</p>
        </div>
    </div>

    {{-- Tabla de Ventas --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alumno</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($data['ventas'] as $venta)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $venta->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($venta->tipo_venta === 'rapida')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Rápida</span>
                            @elseif($venta->tipo_venta === 'producto')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Producto</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-pink-100 text-pink-800">Membresía</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $venta->alumno->nombreCompleto ?? 'Venta rápida' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $venta->producto->prod_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $venta->metodo->metod_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">S/ {{ number_format($venta->venta_total, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($venta->estado_pago === 'pagado')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Pagado</span>
                            @elseif($venta->estado_pago === 'parcial')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Parcial</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Pendiente</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">No se encontraron ventas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
