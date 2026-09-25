<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Sidebar extends Component
{
    public array $menuItems;

    public array $userRole;

    public string $currentRoute;

    public array $initiallyExpandedMenus;

    public function __construct()
    {
        $this->menuItems = $this->getMenuConfig();
        $this->userRole = $this->getUserRoleInfo();
        $this->currentRoute = request()->route()->getName() ?? '';
        $this->initiallyExpandedMenus = $this->getInitiallyExpandedMenus();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.sidebar');
    }

    private function getMenuConfig(): array
    {
        return [
            [
                'id' => 'home',
                'label' => 'Dashboard',
                'icon' => 'fa-house',
                'route' => 'home',
                'roles' => ['Administrador', 'Local', 'Redes', 'Asistencia'],
            ],
            [
                'id' => 'alumnos',
                'label' => 'Alumnos',
                'icon' => 'fa-users',
                'route' => 'alumnos',
                'roles' => ['Administrador', 'Local', 'Redes'],
            ],
            [
                'id' => 'asistencias',
                'label' => 'Asistencia',
                'icon' => 'fa-calendar-check',
                'route' => 'asistencias',
                'roles' => ['Administrador', 'Local', 'Asistencia'],
            ],
            [
                'id' => 'membresias',
                'label' => 'Membresías',
                'icon' => 'fa-award',
                'route' => 'membresias',
                'roles' => ['Administrador', 'Local', 'Redes'],
            ],
            [
                'id' => 'productos',
                'label' => 'Productos',
                'icon' => 'fa-tshirt',
                'route' => 'productos',
                'roles' => ['Administrador', 'Local'],
            ],
            [
                'id' => 'ventas',
                'label' => 'Ventas',
                'icon' => 'fa-cart-plus',
                'route' => 'ventas',
                'roles' => ['Administrador', 'Local', 'Redes'],
                'submenu' => [
                    [
                        'id' => 'ventas-generadas',
                        'label' => 'Ventas Generadas',
                        'icon' => 'fa-money-bill',
                        'route' => 'ventas.index',
                        'roles' => ['Administrador', 'Local', 'Redes'],
                    ],
                ],
            ],
            [
                'id' => 'cobranza',
                'label' => 'Cobranza',
                'icon' => 'fa-money-bill',
                'route' => 'cobranza',
                'roles' => ['Administrador', 'Local', 'Redes'],
                'submenu' => [
                    [
                        'id' => 'cobranza-pendientes',
                        'label' => 'Saldos pendientes',
                        'icon' => 'fa-hand-holding-dollar',
                        'route' => 'cobranza.index',
                        'roles' => ['Administrador', 'Local', 'Redes'],
                    ],
                    [
                        'id' => 'cobranza-vencidas',
                        'label' => 'Cuotas vencidas',
                        'icon' => 'fa-triangle-exclamation',
                        'route' => 'cobranza.vencidas',
                        'roles' => ['Administrador', 'Local', 'Redes'],
                    ],
                    [
                        'id' => 'cobranza-historial',
                        'label' => 'Historial de abonos',
                        'icon' => 'fa-clock-rotate-left',
                        'route' => 'cobranza.historial',
                        'roles' => ['Administrador', 'Local', 'Redes'],
                    ],
                ],
            ],
            [
                'id' => 'gastos',
                'label' => 'Gastos',
                'icon' => 'fa-calculator',
                'route' => 'gastos',
                'roles' => ['Administrador', 'Local'],
            ],
            [
                'id' => 'comisiones',
                'label' => 'Comisiones',
                'icon' => 'fa-percent',
                'route' => 'comisiones',
                'roles' => ['Administrador'],
            ],
            [
                'id' => 'mis-comisiones',
                'label' => 'Mis Comisiones',
                'icon' => 'fa-percent',
                'route' => 'comisiones.mis-comisiones',
                'dynamic_route' => fn () => route('comisiones.mis-comisiones'),
                'roles' => ['Local', 'Redes'],
            ],
            [
                'id' => 'caja',
                'label' => 'Caja',
                'icon' => 'fa-cash-register',
                'route' => 'caja',
                'roles' => ['Administrador', 'Local', 'Redes'],
            ],
            [
                'id' => 'reportes',
                'label' => 'Reportes',
                'icon' => 'fa-file-lines',
                'route' => 'reportes',
                'roles' => ['Administrador'],
                'submenu' => [
                    [
                        'id' => 'reportes-principal',
                        'label' => 'Todos los Reportes',
                        'icon' => 'fa-th-large',
                        'route' => 'reportes.index',
                        'roles' => ['Administrador'],
                    ],
                    [
                        'id' => 'reportes-ventas',
                        'label' => 'Ventas',
                        'icon' => 'fa-shopping-cart',
                        'route' => 'reportes.ventas',
                        'roles' => ['Administrador'],
                    ],
                    [
                        'id' => 'reportes-membresias',
                        'label' => 'Membresías',
                        'icon' => 'fa-id-card',
                        'route' => 'reportes.membresias',
                        'roles' => ['Administrador'],
                    ],
                    [
                        'id' => 'reportes-productos',
                        'label' => 'Productos',
                        'icon' => 'fa-box',
                        'route' => 'reportes.productos',
                        'roles' => ['Administrador'],
                    ],
                    [
                        'id' => 'reportes-comisiones',
                        'label' => 'Comisiones',
                        'icon' => 'fa-percentage',
                        'route' => 'reportes.comisiones',
                        'roles' => ['Administrador'],
                    ],
                    [
                        'id' => 'reportes-gastos',
                        'label' => 'Gastos',
                        'icon' => 'fa-receipt',
                        'route' => 'reportes.gastos',
                        'roles' => ['Administrador'],
                    ],
                    [
                        'id' => 'reportes-caja',
                        'label' => 'Caja',
                        'icon' => 'fa-cash-register',
                        'route' => 'reportes.caja',
                        'roles' => ['Administrador'],
                    ],
                    [
                        'id' => 'reportes-vencimientos',
                        'label' => 'Vencimientos',
                        'icon' => 'fa-calendar-times',
                        'route' => 'reportes.vencimientos',
                        'roles' => ['Administrador'],
                    ],
                ],
            ],
            [
                'id' => 'seguimiento',
                'label' => 'Seguimiento',
                'icon' => 'fa-chart-line',
                'route' => 'seguimiento',
                'roles' => ['Administrador', 'Local', 'Redes'],
            ],
            [
                'id' => 'usuarios',
                'label' => 'Usuarios',
                'icon' => 'fa-user',
                'route' => 'usuarios',
                'roles' => ['Administrador'],
            ],
            [
                'id' => 'sedes',
                'label' => 'Sedes',
                'icon' => 'fa-building',
                'route' => 'sedes',
                'roles' => ['Administrador'],
            ],
            [
                'id' => 'auditoria',
                'label' => 'Auditoría',
                'icon' => 'fa-history',
                'route' => 'auditoria',
                'roles' => ['Administrador'],
            ],
        ];
    }

    private function getInitiallyExpandedMenus(): array
    {
        foreach ($this->menuItems as $item) {
            foreach ($item['submenu'] ?? [] as $subitem) {
                if ($subitem['route'] === $this->currentRoute) {
                    return [$item['route'] => true];
                }
            }
        }

        return [];
    }

    private function getUserRoleInfo(): array
    {
        if (! auth()->check()) {
            return ['icon' => 'fas fa-user', 'text' => 'Invitado'];
        }

        $user = auth()->user();

        if ($user->hasRole('Administrador')) {
            return [
                'icon' => 'fas fa-shield-alt',
                'text' => 'Administrador',
            ];
        }

        if ($user->hasRole('Asistencia')) {
            return [
                'icon' => 'fas fa-calendar-check',
                'text' => 'Asistencia | '.($user->sede->sede_nombre ?? 'Sin sede asignada'),
            ];
        }

        if ($user->hasRole('Local')) {
            return [
                'icon' => 'fas fa-user-tie',
                'text' => 'Local | '.($user->sede->sede_nombre ?? 'Sin sede asignada'),
            ];
        }

        if ($user->hasRole('Redes')) {
            return [
                'icon' => 'fas fa-user',
                'text' => 'Redes | '.($user->name ?? 'Sin nombre'),
            ];
        }

        return [
            'icon' => 'fas fa-user',
            'text' => 'Usuario',
        ];
    }

    public function canAccessMenuItem(array $item): bool
    {
        if (! isset($item['roles']) || ! auth()->check()) {
            return true;
        }

        $user = auth()->user();
        $allowedRoles = $item['roles'];

        foreach ($allowedRoles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determina si una ruta está activa
     */
    public function isActive(string $route): string
    {
        return request()->routeIs($route.'*') ? 'active' : '';
    }
}
