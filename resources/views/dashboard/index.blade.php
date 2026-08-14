@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Resumen general del gimnasio')

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-pink-600">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Alumnos</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalAlumnos ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-pink-600/10 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-pink-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-600">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Ingresos Hoy</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">S/ {{ number_format($ingresosHoy ?? 0, 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-600/10 rounded-lg flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-600">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Ingresos del Mes</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">S/ {{ number_format($ingresosMes ?? 0, 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-600/10 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-pink-600">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Asistencias Hoy</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $asistenciasHoy ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-pink-600/10 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clipboard-check text-pink-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Nuevos Alumnos del Mes</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $nuevosAlumnosMes ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-500/10 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-plus text-yellow-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-600">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Membresias por Vencer</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $membresiasPorVencer ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-red-600/10 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
            </div>
        </div>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Accesos Rapidos</h3>
            <div class="grid grid-cols-2 gap-4">
                <a href="{{ route('alumnos.create') }}" class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:border-pink-600 hover:bg-pink-600/5 transition-colors">
                    <i class="fas fa-user-plus text-pink-600"></i>
                    <span class="text-sm font-medium text-gray-700">Nuevo Alumno</span>
                </a>
                <a href="{{ route('alumnos.index') }}" class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:border-pink-600 hover:bg-pink-600/5 transition-colors">
                    <i class="fas fa-credit-card text-pink-600"></i>
                    <span class="text-sm font-medium text-gray-700">Registrar Pago</span>
                </a>
                <a href="{{ route('asistencias.index') }}" class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:border-pink-600 hover:bg-pink-600/5 transition-colors">
                    <i class="fas fa-door-open text-pink-600"></i>
                    <span class="text-sm font-medium text-gray-700">Asistencia</span>
                </a>
                <a href="{{ route('ventas.index') }}" class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:border-pink-600 hover:bg-pink-600/5 transition-colors">
                    <i class="fas fa-shopping-cart text-pink-600"></i>
                    <span class="text-sm font-medium text-gray-700">Nueva Venta</span>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Reportes</h3>
            <div class="space-y-3">
                <a href="{{ route('reportes.index') }}" class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:border-pink-600 hover:bg-pink-600/5 transition-colors">
                    <i class="fas fa-chart-bar text-pink-600"></i>
                    <span class="text-sm font-medium text-gray-700">Reportes de Pagos</span>
                </a>
                <a href="{{ route('reportes.ventas') }}" class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:border-pink-600 hover:bg-pink-600/5 transition-colors">
                    <i class="fas fa-chart-pie text-pink-600"></i>
                    <span class="text-sm font-medium text-gray-700">Reportes de Ventas</span>
                </a>
                <a href="{{ route('reportes.index') }}" class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:border-pink-600 hover:bg-pink-600/5 transition-colors">
                    <i class="fas fa-file-alt text-pink-600"></i>
                    <span class="text-sm font-medium text-gray-700">Ver Todos los Reportes</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
