@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reporte de Caja</h1>
            <p class="text-gray-600">Cierres de caja y diferencias</p>
        </div>
        <a href="{{ route('reportes.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
            <i class="fas fa-arrow-left mr-2"></i> Volver
        </a>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form method="GET" action="{{ route('reportes.caja') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
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
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Empleado</label>
                <select name="empleado" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Todos</option>
                    @foreach($empleados as $empleado)
                        <option value="{{ $empleado->id }}" {{ request('empleado') == $empleado->id ? 'selected' : '' }}>{{ $empleado->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
                <input type="hidden" name="sede" value="{{ auth()->user()->fksede }}">
                <div></div>
                <div></div>
            @endif
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
            <p class="text-sm text-gray-500">Total Cierres</p>
            <p class="text-3xl font-bold text-indigo-600">{{ $data['cantidad'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Diferencia Total</p>
            <p class="text-3xl font-bold {{ $data['total_diferencia'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                S/ {{ number_format($data['total_diferencia'], 2) }}
            </p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Cierres con Diferencia</p>
            <p class="text-3xl font-bold text-orange-600">{{ $data['con_diferencia'] }}</p>
        </div>
    </div>

    {{-- Tabla de Cierres --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sede</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Apertura</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cierre</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Inicial</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Esperado</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Entregado</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Diferencia</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($data['cajas'] as $caja)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $caja->usuario->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $caja->sede->sede_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $caja->fecha_apertura->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $caja->fecha_cierre ? $caja->fecha_cierre->format('d/m/Y H:i') : '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right">S/ {{ number_format($caja->monto_inicial, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right">S/ {{ number_format($caja->total_ingresos_esperado ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right">S/ {{ number_format($caja->monto_entregado ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-right {{ ($caja->diferencia ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            S/ {{ number_format($caja->diferencia ?? 0, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($caja->estado === 'abierta')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Abierta</span>
                            @elseif($caja->estado === 'cerrada')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Cerrada</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Anulada</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">No se encontraron cierres de caja.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
