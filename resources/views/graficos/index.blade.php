@extends('layouts.app')

@section('title', 'Graficos')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Graficos y Estadisticas</h2>
        <p class="text-sm text-gray-500">Visualizacion de datos del gimnasio</p>
    </div>

    <div class="bg-white rounded-lg shadow p-12 text-center">
        <i class="fas fa-chart-column text-5xl text-gray-300 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Modulo en desarrollo</h3>
        <p class="text-gray-500">Los graficos avanzados estaran disponibles en una proxima actualizacion</p>
        <a href="{{ route('home.index') }}" class="inline-block mt-4 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Volver al Dashboard
        </a>
    </div>
</div>
@endsection
