@extends('layouts.app')

@section('title', 'Reportes de Pagos')
@section('page-title', 'Reportes de Pagos')
@section('page-subtitle', 'Informes y estadisticas de pagos')

@section('content')
<div class="space-y-6">

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <a href="{{ route('reportes.ventas') }}" class="bg-white rounded-lg shadow p-6 hover:border-pink-600 border-2 border-transparent transition-colors">
            <div class="w-12 h-12 bg-pink-600/10 rounded-lg flex items-center justify-center mb-4">
                <i class="fas fa-chart-bar text-pink-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Reporte de Ventas</h3>
            <p class="text-sm text-gray-600">Consulta las ventas realizadas por periodo.</p>
        </a>

        <a href="{{ route('reportes.formulario') }}" class="bg-white rounded-lg shadow p-6 hover:border-pink-600 border-2 border-transparent transition-colors">
            <div class="w-12 h-12 bg-pink-600/10 rounded-lg flex items-center justify-center mb-4">
                <i class="fas fa-file-alt text-pink-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Generar Reportes</h3>
            <p class="text-sm text-gray-600">Crea reportes personalizados con filtros avanzados.</p>
        </a>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="w-12 h-12 bg-pink-600/10 rounded-lg flex items-center justify-center mb-4">
                <i class="fas fa-chart-pie text-pink-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Resumen de Pagos</h3>
            <p class="text-sm text-gray-600">Vista general de todos los pagos registrados.</p>
        </div>
    </div>

</div>
@endsection
