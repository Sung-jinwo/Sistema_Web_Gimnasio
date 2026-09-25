@extends('layouts.app')

@section('page-title','Dashboard administrativo')
@section('page-subtitle','Resumen financiero y operativo de todas las sedes')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- Indicadores puntuales --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <x-stat-card
            title="Ingresos del Mes"
            value="S/ {{ number_format($ingresosMes, 2) }}"
            icon="fa-dollar-sign"
            color="green"
            subtitle="{{ $variacionIngresos === null ? 'Sin referencia previa' : (($variacionIngresos >= 0 ? '+' : '') . $variacionIngresos . '% vs mes anterior') }}"
        />
        <x-stat-card
            title="Alumnos Registrados"
            value="{{ number_format($alumnosActivos) }}"
            icon="fa-users"
            color="purple"
            subtitle="{{ number_format($nuevosAlumnosMes) }} nuevos este mes"
        />
        <x-stat-card
            title="Ventas Hoy"
            value="S/ {{ number_format($ventasHoy, 2) }}"
            icon="fa-shopping-cart"
            color="blue"
        />
    </div>

    {{-- Gráficos --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2">
            <x-grafico
                titulo="Ingresos vs Gastos"
                subtitulo="Últimos 6 meses"
                tipo="bar"
                :etiquetas="$graficoFinanzas['etiquetas']"
                :series="[['etiqueta' => 'Ingresos', 'datos' => $graficoFinanzas['ingresos']], ['etiqueta' => 'Gastos', 'datos' => $graficoFinanzas['gastos']]]"
                moneda
            />
        </div>
        <x-grafico
            titulo="Membresías por estado"
            tipo="doughnut"
            :etiquetas="$graficoMembresias['etiquetas']"
            :series="[['etiqueta' => 'Membresías', 'datos' => $graficoMembresias['datos']]]"
            altura="260px"
        />
    </div>
    <div class="mb-6">
        <x-grafico
            titulo="Ventas por sede"
            subtitulo="Mes actual"
            tipo="bar"
            :etiquetas="$graficoVentasSede['etiquetas']"
            :series="[['etiqueta' => 'Ventas', 'datos' => $graficoVentasSede['datos']]]"
            moneda
            altura="260px"
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
