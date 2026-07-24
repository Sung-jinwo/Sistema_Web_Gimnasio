@extends('layouts.app')

@section('title', 'Asistencias')
@section('page-title', 'Asistencias')
@section('page-subtitle', 'Control de asistencia de alumnos')

@section('content')
<div x-data="{ showRegistrarModal: false, searchQuery: '' }" class="space-y-6">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="w-full sm:w-96">
            <x-search-input placeholder="Buscar por codigo o DNI..." model="searchQuery" />
        </div>
        <x-button @click="showRegistrarModal = true" class="bg-pink-600 hover:bg-pink-700 focus:ring-pink-500">
            <i class="fas fa-plus"></i> Registrar Asistencia
        </x-button>
    </div>

    <x-table :headers="['Fecha', 'Alumno', 'Tipo Ingreso', 'Sede']">
        @forelse($asistencias ?? [] as $asistencia)
        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 text-sm text-gray-700">{{ $asistencia->asis_fecha ?? '' }}</td>
            <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ $asistencia->alumno->alum_nombre ?? '' }} {{ $asistencia->alumno->alum_apellido ?? '' }}</td>
            <td class="px-6 py-4 text-sm">
                <x-badge :variant="$asistencia->asis_tipo_ingreso === 'Membresia' ? 'success' : 'info'">
                    {{ $asistencia->asis_tipo_ingreso ?? '' }}
                </x-badge>
            </td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ $asistencia->sede->sede_nombre ?? '' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-3"></i>
                <p>No hay asistencias registradas</p>
            </td>
        </tr>
        @endforelse
    </x-table>

    @if(isset($asistencias) && $asistencias->hasPages())
    <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600">
            Mostrando {{ $asistencias->firstItem() ?? 0 }} a {{ $asistencias->lastItem() ?? 0 }} de {{ $asistencias->total() }} registros
        </div>
        <div>{{ $asistencias->links() }}</div>
    </div>
    @endif

    <x-modal-form show="showRegistrarModal" title="Registrar Asistencia" size="md" headerColor="red">
        <form action="{{ route('asistencias.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Codigo del Alumno</label>
                <input type="text" name="fkalum" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-600 focus:border-transparent" placeholder="Ingrese codigo o DNI">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Ingreso</label>
                <select name="asis_tipo_ingreso" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-600 focus:border-transparent">
                    <option value="">Seleccione...</option>
                    <option value="Membresia">Membresia</option>
                    <option value="Diario">Diario</option>
                    <option value="Cortesia">Cortesia</option>
                </select>
            </div>
            <div class="flex gap-3 pt-4">
                <x-button variant="outline" @click="showRegistrarModal = false" class="flex-1">
                    Cancelar
                </x-button>
                <x-button type="submit" class="flex-1 bg-pink-600 hover:bg-pink-700 focus:ring-pink-500">
                    Guardar
                </x-button>
            </div>
        </form>
    </x-modal-form>
</div>
@endsection
