@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reporte de Comisiones</h1>
            <p class="text-gray-600">Comisiones y penalizaciones por empleado</p>
        </div>
        <a href="{{ route('reportes.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
            <i class="fas fa-arrow-left mr-2"></i> Volver
        </a>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form method="GET" action="{{ route('reportes.comisiones') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label>
                <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
            </div>
        </form>
    </div>

    {{-- Resumen --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Total Comisiones</p>
            <p class="text-3xl font-bold text-gray-900">{{ $data['cantidad'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Comisión Base</p>
            <p class="text-3xl font-bold text-blue-600">S/ {{ number_format($data['total_base'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Penalizaciones</p>
            <p class="text-3xl font-bold text-red-600">- S/ {{ number_format($data['total_penalizaciones'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Comisión Final</p>
            <p class="text-3xl font-bold text-green-600">S/ {{ number_format($data['total_final'], 2) }}</p>
        </div>
    </div>

    {{-- Resumen por Empleado --}}
    @if($data['por_empleado']->count() > 0)
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Resumen por Empleado</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Base</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Penalización</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Final</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($data['por_empleado'] as $empleado => $montos)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $empleado }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right">S/ {{ number_format($montos['base'], 2) }}</td>
                        <td class="px-4 py-3 text-sm text-red-600 text-right">- S/ {{ number_format($montos['penalizacion'], 2) }}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-green-600 text-right">S/ {{ number_format($montos['final'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Tabla de Comisiones --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">Detalle de Comisiones</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Venta</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Base</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Penalización</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Final</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($data['comisiones'] as $comision)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $comision->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $comision->usuario->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($comision->tipo === 'membresia')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-pink-100 text-pink-800">Membresía</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Venta</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $comision->venta->alumno->nombreCompleto ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right">S/ {{ number_format($comision->comision_base, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-red-600 text-right">- S/ {{ number_format($comision->penalizacion, 2) }}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-green-600 text-right">S/ {{ number_format($comision->comision_final, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($comision->estado === 'liquidada')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Liquidada</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendiente</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">No se encontraron comisiones.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
