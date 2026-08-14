<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencia - SIGG</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-pink-50 via-white to-purple-50 min-h-screen">
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-pink-600 rounded-full mb-4">
                <i class="fas fa-dumbbell text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Registro de Asistencia</h1>
            <p class="text-gray-600 mt-2">Ingrese su DNI o código de alumno</p>
        </div>

        {{-- Mensajes de resultado --}}
        @if(isset($resultado))
            @if($resultado['success'])
                <div class="w-full max-w-md bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg shadow-sm" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-500 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ $resultado['message'] }}</p>
                            @if(isset($resultado['alumno']))
                                <p class="text-sm text-green-700 mt-1">
                                    <strong>{{ $resultado['alumno']->nombreCompleto }}</strong>
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="w-full max-w-md {{ $resultado['tipo'] === 'warning' ? 'bg-yellow-50 border-yellow-500' : 'bg-red-50 border-red-500' }} border-l-4 p-4 mb-6 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas {{ $resultado['tipo'] === 'warning' ? 'fa-exclamation-triangle text-yellow-500' : 'fa-times-circle text-red-500' }} text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium {{ $resultado['tipo'] === 'warning' ? 'text-yellow-800' : 'text-red-800' }}">
                                {{ $resultado['message'] }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        {{-- Formulario --}}
        <div class="w-full max-w-md">
            <form method="POST" action="{{ route('asistencia.publica.store') }}" class="bg-white rounded-xl shadow-lg p-6 space-y-6">
                @csrf

                {{-- Selección de Sede --}}
                <div>
                    <label for="sede_id" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-building mr-2 text-pink-600"></i>Sede
                    </label>
                    <select name="sede_id" id="sede_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent text-gray-900">
                        <option value="">Seleccionar sede...</option>
                        @foreach($sedes as $sede)
                            <option value="{{ $sede->id_sede }}" {{ old('sede_id') == $sede->id_sede ? 'selected' : '' }}>
                                {{ $sede->sede_nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('sede_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Campo DNI/Código --}}
                <div>
                    <label for="codigo_documento" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-id-card mr-2 text-pink-600"></i>DNI o Código de Alumno
                    </label>
                    <input type="text" 
                           name="codigo_documento" 
                           id="codigo_documento" 
                           value="{{ old('codigo_documento') }}"
                           required 
                           autofocus
                           maxlength="20"
                           placeholder="Ingrese su DNI o código"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent text-gray-900 text-lg">
                    @error('codigo_documento')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Botón Registrar --}}
                <button type="submit" class="w-full flex items-center justify-center px-6 py-4 bg-pink-600 text-white font-semibold rounded-lg hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-offset-2 transition-colors text-lg">
                    <i class="fas fa-calendar-check mr-2"></i>
                    Registrar Asistencia
                </button>
            </form>

            {{-- Información adicional --}}
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Si tiene problemas para registrar su asistencia, contacte a recepción
                </p>
            </div>
        </div>

        {{-- Footer --}}
        <div class="mt-8 text-center text-sm text-gray-400">
            <p>SIGG - Sistema Integral de Gestión para Gimnasios</p>
        </div>
    </div>

    {{-- Auto-focus y limpieza del campo después de registrar --}}
    @if(isset($resultado) && $resultado['success'])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.getElementById('codigo_documento').value = '';
                document.getElementById('codigo_documento').focus();
            }, 1000);
        });
    </script>
    @endif
</body>
</html>
