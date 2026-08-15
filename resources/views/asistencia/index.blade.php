@extends('layouts.app')
@section('title', 'Asistencias - SIGG')
@section('page-title', 'Asistencias')
@section('page-subtitle', 'Control de asistencia de alumnos')
@section('content')
<div x-data="{ showRegistrarModal: false }" class="w-full space-y-5">
    <div class="flex flex-wrap justify-end gap-2">
        <button @click="showRegistrarModal = true" class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
            <i class="fas fa-plus mr-2"></i> Registrar Asistencia
        </button>
    </div>

    <form method="GET" action="{{ route('asistencias.index') }}" class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <input name="search" value="{{ request('search') }}" placeholder="Buscar por código o DNI..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            <select name="tipo_ingreso" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todos los tipos</option>
                <option value="codigo" @selected(request('tipo_ingreso')=='codigo')>Código</option>
                <option value="dni" @selected(request('tipo_ingreso')=='dni')>DNI</option>
                <option value="qr" @selected(request('tipo_ingreso')=='qr')>QR</option>
                <option value="huella" @selected(request('tipo_ingreso')=='huella')>Huella</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                <i class="fas fa-filter mr-2"></i> Filtrar
            </button>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha/Hora</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alumno</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo Ingreso</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Sede</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($asistencias ?? [] as $asistencia)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $asistencia->visi_fecha?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $asistencia->alumno->nombreCompleto ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            @php
                                $tipoColor = match($asistencia->tipo_ingreso) {
                                    'codigo' => 'bg-blue-100 text-blue-800',
                                    'dni' => 'bg-purple-100 text-purple-800',
                                    'qr' => 'bg-green-100 text-green-800',
                                    'huella' => 'bg-orange-100 text-orange-800',
                                    default => 'bg-gray-100 text-gray-800',
                                };
                            @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $tipoColor }}">{{ ucfirst($asistencia->tipo_ingreso ?? '') }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500 hidden md:table-cell">{{ $asistencia->sede->sede_nombre ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">No hay asistencias registradas</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($asistencias) && $asistencias->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $asistencias->links() }}
        </div>
        @endif
    </div>

    <x-modal-form show="showRegistrarModal" title="Registrar Asistencia" subtitle="Ingrese el código o DNI del alumno" size="md" headerColor="red">
        <form action="{{ route('asistencias.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Código o DNI del Alumno <span class="text-red-500">*</span></label>
                <input type="text" name="fkalum" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Ingrese código o DNI">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Ingreso <span class="text-red-500">*</span></label>
                <select name="tipo_ingreso" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">Seleccione...</option>
                    <option value="codigo">Código</option>
                    <option value="dni">DNI</option>
                    <option value="qr">QR</option>
                    <option value="huella">Huella</option>
                </select>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" @click="showRegistrarModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Cancelar</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">Registrar</button>
            </div>
        </form>
    </x-modal-form>
</div>
@endsection