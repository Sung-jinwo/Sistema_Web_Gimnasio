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
            <button class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
            </button>
        </div>
    </div>
</header>
