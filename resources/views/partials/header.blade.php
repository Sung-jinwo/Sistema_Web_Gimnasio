<header class="bg-white border-b border-gray-200 sticky top-0 z-10 shadow-sm flex-shrink-0">
    <div class="px-4 lg:px-8 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <button 
                @click="mobileMenuOpen = !mobileMenuOpen"
                class="lg:hidden p-2 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <div>
                <h2 class="text-xl lg:text-2xl font-bold text-gray-900">@yield('page-title', 'Título')</h2>
                <p class="text-xs lg:text-sm text-gray-600">@yield('page-subtitle', 'Subtítulo')</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <x-notification-dropdown />
            
            {{-- Dropdown de Usuario --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <div class="w-8 h-8 bg-pink-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white text-sm"></i>
                    </div>
                    <span class="hidden md:block text-sm font-medium text-gray-700">{{ auth()->user()->name }}</span>
                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                </button>
                
                <div x-show="open" 
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                    <a href="{{ route('password.change.form') }}" class="flex items-center gap-2 px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                        <i class="fas fa-key text-pink-600"></i>
                        Cambiar Contraseña
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2 px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors">
                            <i class="fas fa-sign-out-alt"></i>
                            Cerrar Sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
