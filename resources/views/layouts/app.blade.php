<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'Ivonne Gym - Dashboard')</title>    
    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
    <script src="https://cdn.tailwindcss.com"></script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


    
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
    
</head>
<body class="bg-gray-50" x-data="{ 
    sidebarOpen: window.innerWidth >= 1024, 
    mobileMenuOpen: false,
    expandedMenus: {},
    isMobile: window.innerWidth < 1024,
    sidebarCollapsed: false,

    toggleSubmenu(menuId) {
        this.expandedMenus[menuId] = !this.expandedMenus[menuId];
    },
    isExpanded(menuId) {
        return this.expandedMenus[menuId] || false;
    },
    init() {
        this.sidebarOpen = window.innerWidth >= 1024;
        
        // Escuchar cambios de tamaño
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                this.sidebarOpen = true;
                this.mobileMenuOpen = false;
            } else {
                this.sidebarOpen = false;
            }
        });
    }

    
}" x-init="init()">
  
    <x-toast-notifications />
    <x-page-loader />

    <div class="flex h-screen bg-gray-50">
        <x-sidebar />
        <main class="flex-1 flex flex-col overflow-hidden w-full">
            
            @include('partials.header')

            <div class="flex-1 overflow-auto p-4 lg:p-8 bg-gray-50">
                @include('partials.validation-errors')
                @yield('content')
            </div>
        </main>
    </div>
    @stack('scripts')

</body>
</html>