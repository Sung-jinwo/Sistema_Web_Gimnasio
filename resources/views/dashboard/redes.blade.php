@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Dashboard Redes</h1>
        <p class="text-gray-600">Seguimiento comercial de alumnos</p>
    </div>

    {{-- Métricas de alumnos --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <x-stat-card 
            title="Alumnos Gestionados" 
            value="{{ number_format($alumnosGestionados) }}" 
            icon="fa-users" 
            color="purple" 
        />
        <x-stat-card 
            title="Nuevos Este Mes" 
            value="{{ number_format($nuevosAlumnosMes) }}" 
            icon="fa-user-plus" 
            color="green" 
        />
        <x-stat-card 
            title="Membresías por Vencer" 
            value="{{ number_format($membresiasPorVencer) }}" 
            icon="fa-clock" 
            color="yellow" 
            subtitle="Próximos 5 días"
        />
        <x-stat-card 
            title="Membresías Vencidas" 
            value="{{ number_format($membresiasVencidas) }}" 
            icon="fa-exclamation-triangle" 
            color="red" 
        />
    </div>

    {{-- Seguimiento --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <x-stat-card 
            title="Seguimientos Pendientes" 
            value="{{ number_format($seguimientosPendientes) }}" 
            icon="fa-tasks" 
            color="orange" 
            subtitle="Por vencer + vencidas"
        />
        <x-stat-card 
            title="Notificaciones" 
            value="{{ number_format($totalNoLeidas) }}" 
            icon="fa-bell" 
            color="indigo" 
            subtitle="Sin leer"
        />
    </div>

    {{-- Alertas --}}
    @if($membresiasPorVencer > 0)
    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-clock text-yellow-500 mr-3"></i>
                <p class="text-yellow-700">
                    <strong>{{ $membresiasPorVencer }}</strong> membresía(s) próxima(s) a vencer. ¡Contacta a los alumnos!
                </p>
            </div>
            <a href="{{ route('seguimiento.vencimientos') }}" class="text-yellow-700 underline font-semibold">Ver lista</a>
        </div>
    </div>
    @endif

    @if($membresiasVencidas > 0)
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                <p class="text-red-700">
                    <strong>{{ $membresiasVencidas }}</strong> membresía(s) vencida(s). ¡Recupera estos alumnos!
                </p>
            </div>
            <a href="{{ route('seguimiento.vencidos') }}" class="text-red-700 underline font-semibold">Ver lista</a>
        </div>
    </div>
    @endif

    {{-- Accesos rápidos --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Accesos Rápidos</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <a href="{{ route('alumnos.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-users text-2xl text-blue-600 mb-2"></i>
                <span class="text-sm text-gray-700">Mis Alumnos</span>
            </a>
            <a href="{{ route('seguimiento.vencimientos') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-clock text-2xl text-yellow-600 mb-2"></i>
                <span class="text-sm text-gray-700">Por Vencer</span>
            </a>
            <a href="{{ route('seguimiento.vencidos') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-exclamation-triangle text-2xl text-red-600 mb-2"></i>
                <span class="text-sm text-gray-700">Vencidos</span>
            </a>
            <a href="{{ route('membresias.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-id-card text-2xl text-purple-600 mb-2"></i>
                <span class="text-sm text-gray-700">Membresías</span>
            </a>
            <a href="{{ route('notificaciones.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-bell text-2xl text-indigo-600 mb-2"></i>
                <span class="text-sm text-gray-700">Notificaciones</span>
            </a>
            <a href="{{ route('asistencias.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-calendar-check text-2xl text-green-600 mb-2"></i>
                <span class="text-sm text-gray-700">Asistencias</span>
            </a>
        </div>
    </div>
</div>
@endsection
