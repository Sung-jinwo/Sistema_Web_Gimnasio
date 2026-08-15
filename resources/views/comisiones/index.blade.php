@extends('layouts.app')
@section('title', 'Comisiones - SIGG')
@section('page-title', 'Comisiones por empleado')
@section('page-subtitle', 'Revisa cada venta y liquida únicamente lo seleccionado')
@section('content')
<div class="w-full space-y-5">
    <div class="grid md:grid-cols-3 gap-4">
        <article class="bg-white rounded-lg shadow-sm p-4">
            <p class="text-sm text-gray-500 mb-1">Base</p>
            <b class="text-2xl text-gray-900">S/ {{ number_format($resumen['total_base'], 2) }}</b>
        </article>
        <article class="bg-white rounded-lg shadow-sm p-4">
            <p class="text-sm text-gray-500 mb-1">Penalizaciones</p>
            <b class="text-2xl text-red-600">S/ {{ number_format($resumen['total_penalizaciones'], 2) }}</b>
        </article>
        <article class="bg-white rounded-lg shadow-sm p-4">
            <p class="text-sm text-gray-500 mb-1">Total final</p>
            <b class="text-2xl text-green-600">S/ {{ number_format($resumen['total_final'], 2) }}</b>
        </article>
    </div>

    <form method="GET" action="{{ route('comisiones.index') }}" class="bg-white rounded-lg shadow-sm p-4">
        <div class="grid sm:grid-cols-3 gap-3">
            <select name="empleado" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todos los empleados</option>
                @foreach($empleados as $e)
                    <option value="{{ $e->id }}" @selected(request('empleado') == $e->id)>{{ $e->name }}</option>
                @endforeach
            </select>
            <select name="estado" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todos los estados</option>
                <option value="pendiente" @selected(request('estado') == 'pendiente')>Pendiente</option>
                <option value="liquidada" @selected(request('estado') == 'liquidada')>Liquidada</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                <i class="fas fa-filter mr-2"></i> Filtrar
            </button>
        </div>
    </form>

    <form method="POST" action="{{ route('comisiones.liquidar-seleccion') }}" class="bg-white rounded-lg shadow-sm overflow-hidden">
        @csrf
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900">Comisiones</h3>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-check mr-2"></i> Liquidar seleccionadas
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Sel.</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empleado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Venta / alumno</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Base</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Penalización</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Final</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Detalle</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($comisiones as $c)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-center">
                            @if($c->estado === 'pendiente')
                                <input type="checkbox" name="comisiones[]" value="{{ $c->id_comision }}" class="w-4 h-4 text-pink-600 border-gray-300 rounded focus:ring-pink-500">
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $c->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $c->user->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            @if($c->venta)
                                <div class="font-medium">{{ $c->venta->alumno->nombreCompleto ?? 'Venta rápida' }}</div>
                                <div class="text-xs text-gray-500">{{ ucfirst($c->venta->tipo_venta) }}</div>
                            @else
                                <span class="text-gray-400">Sin venta</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-semibold text-gray-900">S/ {{ number_format($c->comision_base, 2) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-semibold text-red-600">- S/ {{ number_format($c->penalizacion, 2) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-bold text-green-600">S/ {{ number_format($c->comision_final, 2) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            @if($c->estado === 'pendiente')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendiente</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Liquidada</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <a href="{{ route('comisiones.show', $c->id_comision) }}" class="text-blue-600 hover:text-blue-900" title="Ver detalle">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">No hay comisiones registradas</td>
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
    </form>
</div>
@endsection