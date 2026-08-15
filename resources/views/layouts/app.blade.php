<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'SIGG - Sistema de Gestion')</title>    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        [x-cloak] { display: none !important; }
        @media (max-width: 767px) {
            main table.responsive-cards, main table.responsive-cards tbody { display: block; width: 100%; }
            main table.responsive-cards thead { display: none; }
            main table.responsive-cards tbody tr { display: block; margin: .75rem; padding: .75rem; border: 1px solid #e5e7eb; border-radius: .75rem; background: white; box-shadow: 0 1px 2px rgb(0 0 0 / .05); }
            main table.responsive-cards tbody td { display: flex !important; justify-content: space-between; gap: 1rem; width: 100%; padding: .5rem !important; text-align: right !important; white-space: normal !important; }
            main table.responsive-cards tbody td::before { content: attr(data-label); color: #6b7280; font-weight: 600; text-align: left; }
            main table.responsive-cards tbody td[colspan] { display: block !important; text-align: center !important; }
            main table.responsive-cards tbody td[colspan]::before { display: none; }
        }
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('main table').forEach(table => {
                const labels = [...table.querySelectorAll('thead th')].map(th => th.textContent.trim());
                if (!labels.length) return;
                table.classList.add('responsive-cards');
                table.querySelectorAll('tbody tr').forEach(row => [...row.children].forEach((cell, index) => cell.dataset.label = labels[index] || ''));
            });
        });
    </script>

</body>
</html>
