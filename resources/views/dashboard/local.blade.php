@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Dashboard Local</h1>
        <p class="text-gray-600">Resumen de tu sede y operaciones</p>
    </div>

    {{-- Métricas de ventas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <x-stat-card 
            title="Ventas Hoy" 
            value="S/ {{ number_format($ventasHoy, 2) }}" 
            icon="fa-shopping-cart" 
            color="blue" 
        />
        <x-stat-card 
            title="Ventas del Mes" 
            value="S/ {{ number_format($ventasMes, 2) }}" 
            icon="fa-chart-line" 
            color="green" 
        />
        <x-stat-card 
            title="Mi Comisión del Mes" 
            value="S/ {{ number_format($comisionMes, 2) }}" 
            icon="fa-percentage" 
            color="orange" 
        />
    </div>

    {{-- Alumnos y membresías --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <x-stat-card 
            title="Alumnos en Sede" 
            value="{{ number_format($alumnosSede) }}" 
            icon="fa-users" 
            color="purple" 
        />
        <x-stat-card 
            title="Membresías por Vencer" 
            value="{{ number_format($membresiasPorVencer) }}" 
            icon="fa-clock" 
            color="yellow" 
            subtitle="Próximos 5 días"
        />
        <x-stat-card 
            title="Pagos Pendientes" 
            value="{{ number_format($pagosPendientes) }}" 
            icon="fa-money-bill" 
            color="red" 
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
    @if(!$cajaAbierta)
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-cash-register text-blue-500 mr-3"></i>
                <p class="text-blue-700">
                    No tienes caja abierta. <a href="{{ route('caja.index') }}" class="underline font-semibold">Abrir caja</a>
                </p>
            </div>
        </div>
    </div>
    @else
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
        <div class="flex items-center">
            <i class="fas fa-check-circle text-green-500 mr-3"></i>
            <p class="text-green-700">
                Caja abierta. <a href="{{ route('caja.index') }}" class="underline font-semibold">Ir a caja</a>
            </p>
        </div>
    </div>
    @endif

    @if($membresiasPorVencer > 0)
    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-yellow-500 mr-3"></i>
                <p class="text-yellow-700">
                    <strong>{{ $membresiasPorVencer }}</strong> membresía(s) próxima(s) a vencer
                </p>
            </div>
            <a href="{{ route('seguimiento.index') }}" class="text-yellow-700 underline font-semibold">Ver seguimiento</a>
        </div>
    </div>
    @endif

    {{-- Accesos rápidos --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Accesos Rápidos</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('alumnos.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-users text-2xl text-blue-600 mb-2"></i>
                <span class="text-sm text-gray-700">Alumnos</span>
            </a>
            <a href="{{ route('ventas.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-shopping-cart text-2xl text-green-600 mb-2"></i>
                <span class="text-sm text-gray-700">Ventas</span>
            </a>
            <a href="{{ route('caja.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-cash-register text-2xl text-indigo-600 mb-2"></i>
                <span class="text-sm text-gray-700">Caja</span>
            </a>
            <a href="{{ route('notificaciones.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-bell text-2xl text-orange-600 mb-2"></i>
                <span class="text-sm text-gray-700">Notificaciones</span>
            </a>
        </div>
    </div>
</div>
@endsection
