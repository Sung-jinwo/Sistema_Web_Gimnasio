<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - SIGG</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    
    <div class="min-h-screen flex items-center justify-center p-4" x-data="{
        email: '{{ old('email') }}',
        password: '',
        showPassword: false,
        isLoading: false,
        errors: {
            email: '{{ $errors->first('email') }}',
            password: '{{ $errors->first('password') }}'
        },
        
        validateEmail() {
            if (!this.email) {
                this.errors.email = 'El email es requerido';
                return false;
            }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(this.email)) {
                this.errors.email = 'Ingrese un email válido';
                return false;
            }
            this.errors.email = '';
            return true;
        },
        
        validatePassword() {
            if (!this.password) {
                this.errors.password = 'La contraseña es requerida';
                return false;
            }
            if (this.password.length < 5) {
                this.errors.password = 'La contraseña debe tener al menos 5 caracteres';
                return false;
            }
            this.errors.password = '';
            return true;
        },
        
        validateForm() {
            const emailValid = this.validateEmail();
            const passwordValid = this.validatePassword();
            return emailValid && passwordValid;
        },
        
        handleSubmit() {
            if (!this.validateForm()) {
                return;
            }
            this.isLoading = true;
            this.$refs.form.submit();
        }
    }">
        <div class="w-full max-w-md">
            
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-pink-100 rounded-full mb-4">
                    <img src="{{ asset('icon/icongym.png') }}" alt="Logo" class="w-16 h-16 mb-4">
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Ivonne Gym</h1>
                <p class="text-gray-600">Sistema de Gestión</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Iniciar Sesión</h2>

                <!-- Mensajes de Error/Éxito -->
                @if(session('error'))
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-800 text-sm">{{ session('error') }}</p>
                    </div>
                @endif

                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-green-800 text-sm">{{ session('success') }}</p>
                    </div>
                @endif

                <form 
                    x-ref="form"
                    @submit.prevent="handleSubmit"
                    method="POST" 
                    action="{{ route('login') }}" 
                    class="space-y-6">
                    @csrf

                    <!-- Email Input -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-2">Email</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <input
                                type="email"
                                name="email"
                                x-model="email"
                                @blur="validateEmail"
                                @input="errors.email = ''"
                                placeholder="correo@ejemplo.com"
                                :disabled="isLoading"
                                :class="errors.email ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-pink-500'"
                                class="w-full pl-10 pr-4 py-3 border rounded-lg focus:ring-2 focus:border-transparent transition-colors">
                        </div>
                        <p x-show="errors.email" x-text="errors.email" class="text-red-600 text-xs mt-1"></p>
                    </div>

                    <!-- Password Input -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-2">Contraseña</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                name="password"
                                x-model="password"
                                @blur="validatePassword"
                                @input="errors.password = ''"
                                placeholder="••••••••"
                                :disabled="isLoading"
                                :class="errors.password ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-pink-500'"
                                class="w-full pl-10 pr-12 py-3 border rounded-lg focus:ring-2 focus:border-transparent transition-colors">
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                :disabled="isLoading"
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 transition-colors">
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                </svg>
                            </button>
                        </div>
                        <p x-show="errors.password" x-text="errors.password" class="text-red-600 text-xs mt-1"></p>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="w-4 h-4 text-pink-600 border-gray-300 rounded focus:ring-pink-500">
                            <span class="ml-2 text-sm text-gray-600">Recordarme</span>
                        </label>
                        {{-- <a href="{{ route('password.request') }}" class="text-sm text-pink-600 hover:text-pink-700">
                            ¿Olvidaste tu contraseña?
                        </a> --}}
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="isLoading"
                        class="w-full bg-pink-600 hover:bg-pink-700 text-white py-3 rounded-lg font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <span x-show="!isLoading">Iniciar Sesión</span>
                        <span x-show="isLoading" x-cloak>
                            <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Iniciando sesión...
                        </span>
                    </button>
                </form>

                
            </div>

            <!-- Footer -->
            <p class="text-center text-gray-600 text-sm mt-6">
                © 2025 Ivonne Gym. Sistema de Gestión.
            </p>
        </div>
    </div>

</body>
</html>