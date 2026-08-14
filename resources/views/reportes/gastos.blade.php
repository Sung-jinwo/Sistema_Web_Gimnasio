@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reporte de Gastos</h1>
            <p class="text-gray-600">Análisis de gastos por período</p>
        </div>
        <a href="{{ route('reportes.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
            <i class="fas fa-arrow-left mr-2"></i> Volver
        </a>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form method="GET" action="{{ route('reportes.gastos') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label>
                <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select name="estado" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Todos</option>
                    <option value="pendiente" {{ request('estado') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="aprobado" {{ request('estado') === 'aprobado' ? 'selected' : '' }}>Aprobado</option>
                    <option value="rechazado" {{ request('estado') === 'rechazado' ? 'selected' : '' }}>Rechazado</option>
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
            <p class="text-sm text-gray-500">Total Gastos</p>
            <p class="text-3xl font-bold text-red-600">S/ {{ number_format($data['total'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Cantidad</p>
            <p class="text-3xl font-bold text-gray-900">{{ $data['cantidad'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Promedio</p>
            <p class="text-3xl font-bold text-orange-600">S/ {{ number_format($data['cantidad'] > 0 ? $data['total'] / $data['cantidad'] : 0, 2) }}</p>
        </div>
    </div>

    {{-- Resumen por Categoría --}}
    @if($data['por_categoria']->count() > 0)
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Resumen por Categoría</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($data['por_categoria'] as $categoria => $total)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $categoria }}</td>
                        <td class="px-4 py-3 text-sm text-red-600 text-right">S/ {{ number_format($total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Tabla de Gastos --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($data['gastos'] as $gasto)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500">{{ \Carbon\Carbon::parse($gasto->gas_fecha)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $gasto->gas_concepto }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $gasto->categoria->cat_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $gasto->user->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-red-600 text-right">S/ {{ number_format($gasto->gas_monto, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($gasto->estado === 'pendiente')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendiente</span>
                            @elseif($gasto->estado === 'aprobado')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Aprobado</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Rechazado</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">No se encontraron gastos.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
