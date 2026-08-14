<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - SIGG</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-pink-50 via-white to-purple-50 min-h-screen">
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        {{-- Logo y título --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-pink-600 rounded-full mb-4">
                <i class="fas fa-dumbbell text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Recuperar Contraseña</h1>
            <p class="text-gray-600 mt-2">Ingrese su correo electrónico para recibir el enlace de recuperación</p>
        </div>

        {{-- Mensajes de estado --}}
        @if (session('status'))
            <div class="w-full max-w-md bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg shadow-sm">
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
            <div class="w-full max-w-md bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg shadow-sm">
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
        <div class="w-full max-w-md">
            <form method="POST" action="{{ route('password.email') }}" class="bg-white rounded-xl shadow-lg p-6 space-y-6">
                @csrf

                {{-- Campo Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2 text-pink-600"></i>Correo Electrónico
                    </label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           value="{{ old('email') }}"
                           required 
                           autofocus
                           placeholder="correo@ejemplo.com"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent text-gray-900">
                </div>

                {{-- Botón Enviar --}}
                <button type="submit" class="w-full flex items-center justify-center px-6 py-4 bg-pink-600 text-white font-semibold rounded-lg hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-offset-2 transition-colors text-lg">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Enviar Enlace de Recuperación
                </button>
            </form>

            {{-- Enlace para volver al login --}}
            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-pink-600 hover:text-pink-700 font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Volver al inicio de sesión
                </a>
            </div>
        </div>

        {{-- Footer --}}
        <div class="mt-8 text-center text-sm text-gray-400">
            <p>SIGG - Sistema Integral de Gestión para Gimnasios</p>
        </div>
    </div>
</body>
</html>
