@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Dashboard Asistencia</h1>
        <p class="text-gray-600">Resumen de asistencias del día</p>
    </div>

    {{-- Métrica principal --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <x-stat-card 
            title="Asistencias Hoy" 
            value="{{ number_format($asistenciasHoy) }}" 
            icon="fa-calendar-check" 
            color="green" 
        />
    </div>

    {{-- Accesos rápidos --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Accesos Rápidos</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <a href="{{ route('asistencias.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-calendar-check text-2xl text-green-600 mb-2"></i>
                <span class="text-sm text-gray-700">Registrar Asistencia</span>
            </a>
            <a href="{{ route('alumnos.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-users text-2xl text-blue-600 mb-2"></i>
                <span class="text-sm text-gray-700">Buscar Alumno</span>
            </a>
        </div>
    </div>
</div>
@endsection
