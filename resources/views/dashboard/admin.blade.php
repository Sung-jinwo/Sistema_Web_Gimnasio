@extends('layouts.app')

@section('page-title','Dashboard administrativo')
@section('page-subtitle','Resumen financiero y operativo de todas las sedes')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- Métricas principales --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
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
            title="Alumnos Activos" 
            value="{{ number_format($alumnosActivos) }}" 
            icon="fa-users" 
            color="purple" 
        />
        <x-stat-card 
            title="Asistencias Hoy" 
            value="{{ number_format($asistenciasHoy) }}" 
            icon="fa-calendar-check" 
            color="indigo" 
        />
    </div>

    {{-- Membresías --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <x-stat-card 
            title="Membresías Activas" 
            value="{{ number_format($membresiasActivas) }}" 
            icon="fa-id-card" 
            color="green" 
        />
        <x-stat-card 
            title="Por Vencer (5 días)" 
            value="{{ number_format($membresiasPorVencer) }}" 
            icon="fa-clock" 
            color="yellow" 
        />
        <x-stat-card 
            title="Membresías Vencidas" 
            value="{{ number_format($membresiasVencidas) }}" 
            icon="fa-exclamation-triangle" 
            color="red" 
        />
    </div>

    {{-- Finanzas --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <x-stat-card 
            title="Ingresos del Mes" 
            value="S/ {{ number_format($ingresosMes, 2) }}" 
            icon="fa-dollar-sign" 
            color="green" 
        />
        <x-stat-card 
            title="Gastos del Mes" 
            value="S/ {{ number_format($gastosMes, 2) }}" 
            icon="fa-receipt" 
            color="red" 
        />
        <x-stat-card 
            title="Comisiones del Mes" 
            value="S/ {{ number_format($comisionesMes, 2) }}" 
            icon="fa-percentage" 
            color="orange" 
        />
        <x-stat-card 
            title="Productos Vendidos" 
            value="{{ number_format($productosVendidos) }}" 
            icon="fa-box" 
            color="blue" 
            subtitle="Este mes"
        />
    </div>

    {{-- Alertas --}}
    @if($cierresPendientes > 0)
    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-yellow-500 mr-3"></i>
            <p class="text-yellow-700">
                <strong>{{ $cierresPendientes }}</strong> caja(s) abierta(s) pendiente(s) de cierre
            </p>
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
            <a href="{{ route('seguimiento.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-chart-line text-2xl text-purple-600 mb-2"></i>
                <span class="text-sm text-gray-700">Seguimiento</span>
            </a>
            <a href="{{ route('reportes.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-file-alt text-2xl text-orange-600 mb-2"></i>
                <span class="text-sm text-gray-700">Reportes</span>
            </a>
        </div>
    </div>
</div>
@endsection
