@extends('layouts.app')

@section('title', 'Reporte mensual - Alumnos - SIGG')
@section('page-title', 'Reporte mensual de membresías')
@section('page-subtitle', 'Vista previa y exportación a Excel')
@section('content')
<div class="w-full space-y-5">
    <div class="flex flex-wrap justify-between items-center gap-2">
        <a href="{{ route('alumnos.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
            <i class="fas fa-arrow-left mr-2"></i> Volver a Alumnos
        </a>
        <a href="{{ route('alumnos.reporte.exportar', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
            <i class="fas fa-file-excel mr-2"></i> Exportar Excel
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-700">
            <p><span class="font-semibold">Periodo:</span> {{ str_pad($filtros['mes'], 2, '0', STR_PAD_LEFT) }}/{{ $filtros['anio'] }}</p>
            <p><span class="font-semibold">Alcance:</span> {{ $filtros['alcance'] === 'vencen' ? 'Vencen en el mes' : 'Todos los del mes' }}</p>
            <p><span class="font-semibold">Resultados:</span> {{ $registros->total() }}</p>
        </div>

        @if($esAdmin)
        <form method="GET" action="{{ route('alumnos.reporte') }}" class="flex flex-col sm:flex-row gap-3 mt-3">
            <input type="hidden" name="mes" value="{{ $filtros['mes'] }}">
            <input type="hidden" name="anio" value="{{ $filtros['anio'] }}">
            <input type="hidden" name="alcance" value="{{ $filtros['alcance'] }}">
            @foreach($filtros['columnas'] as $col)
                <input type="hidden" name="columnas[]" value="{{ $col }}">
            @endforeach
            <select name="sede" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todas las sedes</option>
                @foreach($sedes as $sede)
                    <option value="{{ $sede->id_sede }}" @selected(($filtros['sede'] ?? null) == $sede->id_sede)>{{ $sede->sede_nombre }}</option>
                @endforeach
            </select>
            <select name="registrador" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todos los registradores</option>
                @foreach($registradores as $registrador)
                    <option value="{{ $registrador->id }}" @selected(($filtros['registrador'] ?? null) == $registrador->id)>{{ $registrador->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                <i class="fas fa-filter mr-2"></i> Filtrar
            </button>
        </form>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table data-responsive="off" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @foreach($encabezados as $encabezado)
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ $encabezado }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($registros as $fila)
                    <tr class="hover:bg-gray-50">
                        @foreach($fila as $valor)
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $valor }}</td>
                        @endforeach
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ count($encabezados) }}" class="px-4 py-8 text-center text-gray-500">No hay membresías para el periodo seleccionado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($registros->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $registros->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
