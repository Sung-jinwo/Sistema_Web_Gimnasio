@extends('layouts.app')

@section('title', 'Pagos Masivos')
@section('page-title', 'Pagos Masivos')
@section('page-subtitle', 'Procesamiento de pagos en lote')

@section('content')
<div class="space-y-6">

    <div class="bg-white rounded-lg shadow p-8 text-center">
        <div class="w-16 h-16 bg-pink-600/10 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-layer-group text-pink-600 text-3xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Pagos Masivos</h3>
        <p class="text-gray-600 mb-6">Procesa multiples pagos de alumnos de forma simultanea.</p>
        <x-button class="bg-pink-600 hover:bg-pink-700 focus:ring-pink-500">
            <i class="fas fa-play"></i> Iniciar Proceso Masivo
        </x-button>
    </div>

</div>
@endsection
