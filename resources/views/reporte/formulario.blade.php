@extends('layouts.app')

@section('title', 'Generar Reportes')
@section('page-title', 'Generar Reportes')
@section('page-subtitle', 'Crea reportes personalizados')

@section('content')
<div class="space-y-6">

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Filtros del Reporte</h3>
        <form class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-600 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label>
                    <input type="date" name="fecha_fin" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-600 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Reporte</label>
                    <select name="tipo" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-600 focus:border-transparent">
                        <option value="">Seleccione...</option>
                        <option value="pagos">Pagos</option>
                        <option value="ventas">Ventas</option>
                        <option value="asistencias">Asistencias</option>
                        <option value="alumnos">Alumnos</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sede</label>
                    <select name="sede" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-600 focus:border-transparent">
                        <option value="">Todas las sedes</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <x-button variant="outline">
                    <i class="fas fa-times"></i> Limpiar
                </x-button>
                <x-button type="submit" class="bg-pink-600 hover:bg-pink-700 focus:ring-pink-500">
                    <i class="fas fa-search"></i> Generar Reporte
                </x-button>
            </div>
        </form>
    </div>

</div>
@endsection
