@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reporte de Membresías</h1>
            <p class="text-gray-600">Estado de membresías de alumnos</p>
        </div>
        <a href="{{ route('reportes.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
            <i class="fas fa-arrow-left mr-2"></i> Volver
        </a>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form method="GET" action="{{ route('reportes.membresias') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select name="estado" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Todos</option>
                    <option value="activa" {{ request('estado') === 'activa' ? 'selected' : '' }}>Activas</option>
                    <option value="por_vencer" {{ request('estado') === 'por_vencer' ? 'selected' : '' }}>Por Vencer (5 días)</option>
                    <option value="vencida" {{ request('estado') === 'vencida' ? 'selected' : '' }}>Vencidas</option>
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
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
            </div>
        </form>
    </div>

    {{-- Resumen --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <p class="text-sm text-gray-500">Total Membresías</p>
        <p class="text-3xl font-bold text-purple-600">{{ $data['cantidad'] }}</p>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alumno</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">DNI</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Membresía</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inicio</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimiento</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sede</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($data['membresias'] as $membresia)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $membresia->alumno->nombreCompleto ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $membresia->alumno->alum_numDoc ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $membresia->membresia->mem_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ \Carbon\Carbon::parse($membresia->fecha_inicio)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ \Carbon\Carbon::parse($membresia->fecha_fin)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $membresia->alumno->sede->sede_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $hoy = now()->format('Y-m-d');
                                $vencida = $membresia->estado === 'vencida' || $membresia->fecha_fin < $hoy;
                                $porVencer = !$vencida && now()->diffInDays(\Carbon\Carbon::parse($membresia->fecha_fin)) <= 5;
                            @endphp
                            @if($vencida)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Vencida</span>
                            @elseif($porVencer)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Por Vencer</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activa</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">No se encontraron membresías.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
