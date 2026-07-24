@extends('layouts.app')

@section('title', 'Inicio')
@section('page-title', 'Bienvenido')
@section('page-subtitle', 'Sistema de Gestion - Ivonne Gym')

@section('content')
<div class="flex items-center justify-center min-h-[60vh]">
    <div class="text-center space-y-6">
        <div class="w-20 h-20 bg-pink-600/10 rounded-full flex items-center justify-center mx-auto">
            <i class="fas fa-dumbbell text-pink-600 text-4xl"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-900">Bienvenido a Ivonne Gym</h1>
        <p class="text-gray-600 max-w-md">Sistema de gestion integral para tu gimnasio. Administra alumnos, pagos, membresias y mas.</p>
        <a href="{{ route('dashboard.index') }}" class="inline-flex items-center gap-2 bg-pink-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-pink-700 transition-colors">
            <i class="fas fa-tachometer-alt"></i>
            Ir al Dashboard
        </a>
    </div>
</div>
@endsection
