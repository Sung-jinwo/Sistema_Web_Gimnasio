@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Mis Comisiones</h1>
        <a href="{{ route('comisiones.index') }}" class="inline-flex items-center px-3 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4">
            <p class="text-sm text-gray-500">Comisión Base Total</p>
            <p class="text-2xl font-bold text-gray-900">S/ {{ number_format($resumen['total_base'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4">
            <p class="text-sm text-gray-500">Total Penalizaciones</p>
            <p class="text-2xl font-bold text-red-600">- S/ {{ number_format($resumen['total_penalizaciones'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4">
            <p class="text-sm text-gray-500">Comisión Final Total</p>
            <p class="text-2xl font-bold text-green-600">S/ {{ number_format($resumen['total_final'], 2) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <form method="GET" action="{{ route('comisiones.mis-comisiones') }}" class="flex flex-col sm:flex-row gap-3">
            <select name="estado" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todos los estados</option>
                <option value="pendiente" {{ request('estado') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="liquidada" {{ request('estado') === 'liquidada' ? 'selected' : '' }}>Liquidada</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                <i class="fas fa-filter mr-2"></i> Filtrar
            </button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Venta</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Base</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Penalización</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Final</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($comisiones as $comision)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $comision->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($comision->tipo === 'membresia')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-pink-100 text-pink-800">Membresía</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Venta</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            @if($comision->venta)
                                {{ $comision->venta->alumno->nombreCompleto ?? 'Venta rápida' }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">S/ {{ number_format($comision->comision_base, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-red-600 font-semibold hidden md:table-cell">- S/ {{ number_format($comision->penalizacion, 2) }}</td>
                        <td class="px-4 py-3 text-sm font-bold text-green-600">S/ {{ number_format($comision->comision_final, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($comision->estado === 'liquidada')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Liquidada</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendiente</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('comisiones.show', $comision->id_comision) }}" class="text-blue-600 hover:text-blue-900" title="Ver detalle">
                                <i class="fas fa-eye"></i>
                            </a>
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
        @if($comisiones->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $comisiones->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
