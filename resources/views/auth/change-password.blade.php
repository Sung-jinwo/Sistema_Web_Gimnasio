@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-2xl mx-auto">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Cambiar Contraseña</h1>
            <p class="text-gray-600">Actualice su contraseña de acceso al sistema</p>
        </div>

        {{-- Mensajes de estado --}}
        @if (session('status'))
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('status') }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Errores de validación --}}
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        @foreach ($errors->all() as $error)
                            <p class="text-sm text-red-800">{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Formulario --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <form method="POST" action="{{ route('password.change') }}" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Contraseña Actual --}}
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2 text-pink-600"></i>Contraseña Actual
                    </label>
                    <input type="password" 
                           name="current_password" 
                           id="current_password" 
                           required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent text-gray-900">
                </div>

                {{-- Nueva Contraseña --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-key mr-2 text-pink-600"></i>Nueva Contraseña
                    </label>
                    <input type="password" 
                           name="password" 
                           id="password" 
                           required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent text-gray-900">
                    <p class="text-xs text-gray-500 mt-1">Mínimo 8 caracteres</p>
                </div>

                {{-- Confirmar Nueva Contraseña --}}
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-key mr-2 text-pink-600"></i>Confirmar Nueva Contraseña
                    </label>
                    <input type="password" 
                           name="password_confirmation" 
                           id="password_confirmation" 
                           required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent text-gray-900">
                </div>

                {{-- Botones --}}
                <div class="flex gap-4 pt-4">
                    <a href="{{ route('home.index') }}" class="flex-1 px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors text-center">
                        Cancelar
                    </a>
                    <button type="submit" class="flex-1 px-6 py-3 bg-pink-600 text-white font-semibold rounded-lg hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-offset-2 transition-colors">
                        <i class="fas fa-save mr-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>

        {{-- Información de seguridad --}}
        <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Información de Seguridad</h3>
                    <ul class="mt-2 text-sm text-blue-700 list-disc list-inside">
                        <li>Su contraseña debe tener al menos 8 caracteres</li>
                        <li>Use una combinación de letras, números y símbolos</li>
                        <li>No comparta su contraseña con nadie</li>
                        <li>Cambie su contraseña regularmente</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
