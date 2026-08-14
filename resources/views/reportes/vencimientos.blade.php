@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reporte de Vencimientos</h1>
            <p class="text-gray-600">Membresías por vencer y vencidas</p>
        </div>
        <a href="{{ route('reportes.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
            <i class="fas fa-arrow-left mr-2"></i> Volver
        </a>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form method="GET" action="{{ route('reportes.vencimientos') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                <select name="mes" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Todos</option>
                    @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ request('mes') == $i ? 'selected' : '' }}>
                            {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                        </option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                <select name="anio" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Todos</option>
                    @for($i = now()->year - 1; $i <= now()->year + 1; $i++)
                        <option value="{{ $i }}" {{ request('anio') == $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
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
                    <option value="por_vencer" {{ request('estado') === 'por_vencer' ? 'selected' : '' }}>Por Vencer</option>
                    <option value="vencido" {{ request('estado') === 'vencido' ? 'selected' : '' }}>Vencidos</option>
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
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <p class="text-sm text-gray-500">Total Vencimientos</p>
        <p class="text-3xl font-bold text-orange-600">{{ $data['cantidad'] }}</p>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alumno</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">DNI</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Membresía</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimiento</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sede</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acción</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($data['vencimientos'] as $vencimiento)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $vencimiento->alumno->nombreCompleto ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $vencimiento->alumno->alum_numDoc ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $vencimiento->alumno->alum_telefo ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $vencimiento->membresia->mem_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ \Carbon\Carbon::parse($vencimiento->fecha_fin)->format('d/m/Y') }}
                            @php
                                $hoy = now()->format('Y-m-d');
                                $vencida = $vencimiento->estado === 'vencida' || $vencimiento->fecha_fin < $hoy;
                            @endphp
                            @if($vencida)
                                <span class="text-xs text-red-600 block">
                                    ({{ \Carbon\Carbon::parse($vencimiento->fecha_fin)->diffInDays(now()) }} días)
                                </span>
                            @else
                                <span class="text-xs text-yellow-600 block">
                                    ({{ now()->diffInDays(\Carbon\Carbon::parse($vencimiento->fecha_fin)) }} días)
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $vencimiento->alumno->sede->sede_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($vencida)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Vencida</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Por Vencer</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($vencimiento->alumno && $vencimiento->alumno->alum_telefo)
                                <a href="https://wa.me/51{{ substr(preg_replace('/[^0-9]/', '', $vencimiento->alumno->alum_telefo), 1) }}?text={{ urlencode('Hola ' . $vencimiento->alumno->alum_nombre . ', tu membresía está próxima a vencer.') }}" 
                                   target="_blank" 
                                   class="inline-flex items-center px-2 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700">
                                    <i class="fab fa-whatsapp mr-1"></i> WhatsApp
                                </a>
                            @else
                                <span class="text-xs text-gray-400">Sin teléfono</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">No se encontraron vencimientos.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
