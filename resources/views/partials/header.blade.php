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

            <a href="{{ route('password.change.form') }}"
               class="p-2 text-gray-600 hover:text-pink-600 hover:bg-gray-100 rounded-lg transition-colors"
               title="Cambiar contraseña"
               aria-label="Cambiar contraseña">
                <i class="fas fa-key"></i>
            </a>
        </div>
    </div>
</header>
