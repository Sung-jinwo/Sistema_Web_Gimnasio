@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Reportes</h1>
        <p class="text-gray-600">Selecciona un tipo de reporte para generar</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {{-- Reporte de Ventas --}}
        <a href="{{ route('reportes.ventas') }}" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Ventas</h3>
                    <p class="text-sm text-gray-500">Reporte de ventas por período</p>
                </div>
            </div>
        </a>

        {{-- Reporte de Membresías --}}
        <a href="{{ route('reportes.membresias') }}" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-id-card text-purple-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Membresías</h3>
                    <p class="text-sm text-gray-500">Estado de membresías</p>
                </div>
            </div>
        </a>

        {{-- Reporte de Productos --}}
        <a href="{{ route('reportes.productos') }}" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-box text-green-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Productos</h3>
                    <p class="text-sm text-gray-500">Inventario y stock</p>
                </div>
            </div>
        </a>

        {{-- Reporte de Comisiones --}}
        <a href="{{ route('reportes.comisiones') }}" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-percentage text-yellow-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Comisiones</h3>
                    <p class="text-sm text-gray-500">Comisiones y penalizaciones</p>
                </div>
            </div>
        </a>

        {{-- Reporte de Gastos --}}
        <a href="{{ route('reportes.gastos') }}" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-receipt text-red-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Gastos</h3>
                    <p class="text-sm text-gray-500">Gastos por período</p>
                </div>
            </div>
        </a>

        {{-- Reporte de Caja --}}
        <a href="{{ route('reportes.caja') }}" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-cash-register text-indigo-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Caja</h3>
                    <p class="text-sm text-gray-500">Cierres de caja</p>
                </div>
            </div>
        </a>

        {{-- Reporte de Vencimientos --}}
        <a href="{{ route('reportes.vencimientos') }}" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar-times text-orange-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Vencimientos</h3>
                    <p class="text-sm text-gray-500">Membresías por vencer</p>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection
