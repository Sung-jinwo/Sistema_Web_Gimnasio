@extends('layouts.app')

@section('page-title','Dashboard Redes')
@section('page-subtitle','Seguimiento comercial de alumnos')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Métricas de ventas propias --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <x-stat-card
            title="Mis Ventas Hoy"
            value="S/ {{ number_format($ventasHoy, 2) }}"
            icon="fa-shopping-cart"
            color="blue"
        />
        <x-stat-card
            title="Mis Ventas del Mes"
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

    {{-- Gráficos --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <x-grafico
            titulo="Mis ventas del mes"
            subtitulo="Por semana"
            tipo="bar"
            :etiquetas="$graficoMisVentas['etiquetas']"
            :series="[['etiqueta' => 'Ventas', 'datos' => $graficoMisVentas['datos']]]"
            moneda
            altura="260px"
        />
        <x-grafico
            titulo="Mi seguimiento"
            subtitulo="Por vencer vs vencidas"
            tipo="doughnut"
            :etiquetas="$graficoSeguimiento['etiquetas']"
            :series="[['etiqueta' => 'Membresías', 'datos' => $graficoSeguimiento['datos']]]"
            altura="260px"
        />
    </div>

    {{-- Alertas --}}
    @if(!$cajaAbierta)
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <div class="flex items-center">
            <i class="fas fa-cash-register text-blue-500 mr-3"></i>
            <p class="text-blue-700">
                No tienes caja abierta. <a href="{{ route('caja.index') }}" class="underline font-semibold">Abrir caja</a>
            </p>
        </div>
    </div>
    @endif

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
            <a href="{{ route('ventas.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-shopping-cart text-2xl text-green-600 mb-2"></i>
                <span class="text-sm text-gray-700">Mis Ventas</span>
            </a>
            <a href="{{ route('cobranza.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-hand-holding-dollar text-2xl text-teal-600 mb-2"></i>
                <span class="text-sm text-gray-700">Cobranza</span>
            </a>
            <a href="{{ route('caja.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-cash-register text-2xl text-indigo-600 mb-2"></i>
                <span class="text-sm text-gray-700">Mi Caja</span>
            </a>
            <a href="{{ route('comisiones.mis-comisiones') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-percent text-2xl text-orange-600 mb-2"></i>
                <span class="text-sm text-gray-700">Mis Comisiones</span>
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
