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

    public function __construct()
    {
        $this->menuItems = $this->getMenuConfig();
        $this->userRole = $this->getUserRoleInfo();
        $this->currentRoute = request()->route()->getName() ?? '';
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
                'label' => 'Home',
                'icon' => 'fa-house',
                'route' => 'home',
                'roles' => ['admin', 'empleado', 'asistencia', 'ventas'],
            ],
            [
                'id' => 'alumnos',
                'label' => 'Alumnos',
                'icon' => 'fa-users',
                'route' => 'alumnos',
                'roles' => ['admin', 'empleado', 'ventas'],
                'submenu' => [
                    [
                        'id' => 'alumnos-listado',
                        'label' => 'Listado de Alumnos',
                        'icon' => 'fa-user-graduate',
                        'route' => 'alumnos.index',
                    ],
                    [
                        'id' => 'alumnos-prospecto',
                        'label' => 'Prospecto Redes',
                        'icon' => 'fa-users-slash',
                        'route' => 'registro.index',
                    ],
                ],
            ],
            [
                'id' => 'asistencia',
                'label' => 'Asistencia',
                'icon' => 'fa-calendar-check',
                'route' => 'asistencia',
                'roles' => ['admin', 'empleado', 'asistencia'],
            ],
            [
                'id' => 'membresias',
                'label' => 'Membresías',
                'icon' => 'fa-award',
                'route' => 'membresias',
                'roles' => ['admin', 'empleado', 'ventas'],
            ],
            [
                'id' => 'productos',
                'label' => 'Productos',
                'icon' => 'fa-tshirt',
                'route' => 'productos',
                'roles' => ['admin', 'empleado', 'ventas'],
            ],
            [
                'id' => 'ventas',
                'label' => 'Ventas',
                'icon' => 'fa-cart-plus',
                'route' => 'ventas',
                'roles' => ['admin', 'empleado', 'ventas'],
                'submenu' => [
                    [
                        'id' => 'ventas-generadas',
                        'label' => 'Ventas Generadas',
                        'icon' => 'fa-money-bill',
                        'route' => 'ventas.index',
                    ],
                    [
                        'id' => 'ventas-reservados',
                        'label' => 'Listado de Reservados',
                        'icon' => 'fa-clock',
                        'route' => 'ventas.reservados',
                    ],
                ],
            ],
            [
                'id' => 'pagos',
                'label' => 'Pagos',
                'icon' => 'fa-money-bill',
                'route' => 'pagos',
                'roles' => ['admin', 'empleado', 'ventas'],
                'submenu' => [
                    [
                        'id' => 'pagos-completos',
                        'label' => 'Pagos Completos',
                        'icon' => 'fa-money-bill-wave',
                        'route' => 'pagos.completos',
                    ],
                    [
                        'id' => 'pagos-incompletos',
                        'label' => 'Pagos Incompletos',
                        'icon' => 'fa-money-bill-1',
                        'route' => 'pagos.incompletos',
                    ],
                ],
            ],
            [
                'id' => 'gastos',
                'label' => 'Gastos',
                'icon' => 'fa-calculator',
                'route' => 'gastos',
                'roles' => ['admin', 'empleado', 'ventas'],
            ],
            [
                'id' => 'reportes',
                'label' => 'Reporte',
                'icon' => 'fa-file-lines',
                'route' => 'reportes',
                'roles' => ['admin', 'empleado', 'ventas'],
                'submenu' => [
                    [
                        'id' => 'reportes-pagos',
                        'label' => 'Listado de pagos',
                        'icon' => 'fa-money-bill',
                        'route' => 'reportes.index',
                        'roles' => ['admin'],
                    ],
                    [
                        'id' => 'reportes-ventas',
                        'label' => 'Listado de Ventas',
                        'icon' => 'fa-money-bill',
                        'route' => 'reportes.ventas',
                        'roles' => ['admin'],
                    ],
                    [
                        'id' => 'reportes-formulario',
                        'label' => 'Generar Reportes',
                        'icon' => 'fa-file-lines',
                        'route' => 'reportes.formulario',
                    ],
                ],
            ],
            [
                'id' => 'usuarios',
                'label' => 'Usuario',
                'icon' => 'fa-user',
                'route' => 'usuarios',
                'roles' => ['admin'],
            ],
            [
                'id' => 'graficos',
                'label' => 'Gráficos',
                'icon' => 'fa-chart-column',
                'route' => 'graficos',
                'roles' => ['admin'],
            ],
        ];
    }

    private function getUserRoleInfo(): array
    {
        if (!auth()->check()) {
            return ['icon' => 'fas fa-user', 'text' => 'Invitado'];
        }

        $user = auth()->user();

        // Asume que tienes constantes ROL_ADMIN, ROL_ASISTENCIA, etc.
        if (defined('App\Models\User::ROL_ADMIN') && $user->is(constant('App\Models\User::ROL_ADMIN'))) {
            return [
                'icon' => 'fas fa-shield-alt',
                'text' => 'Administrador',
            ];
        }

        if (defined('App\Models\User::ROL_ASISTENCIA') && $user->is(constant('App\Models\User::ROL_ASISTENCIA'))) {
            return [
                'icon' => 'fas fa-calendar-check',
                'text' => 'Asistencia | ' . ($user->sede->sede_nombre ?? 'Sin sede asignada'),
            ];
        }

        if (defined('App\Models\User::ROL_EMPLEADO') && $user->is(constant('App\Models\User::ROL_EMPLEADO'))) {
            return [
                'icon' => 'fas fa-user-tie',
                'text' => 'Empleado | ' . ($user->sede->sede_nombre ?? 'Sin sede asignada'),
            ];
        }

        if (defined('App\Models\User::ROL_VENTAS') && $user->is(constant('App\Models\User::ROL_VENTAS'))) {
            return [
                'icon' => 'fas fa-user',
                'text' => 'Ventas | ' . ($user->name ?? 'Sin nombre'),
            ];
        }

        return [
            'icon' => 'fas fa-user',
            'text' => 'Usuario',
        ];
    }

    /**
     * Verifica si el usuario puede acceder a un item del menú
     */
    public function canAccessMenuItem(array $item): bool
    {
        if (!isset($item['roles']) || !auth()->check()) {
            return true;
        }

        $user = auth()->user();
        $allowedRoles = $item['roles'];

        foreach ($allowedRoles as $role) {
            $roleConstant = 'App\Models\User::ROL_' . strtoupper($role);
            if (defined($roleConstant) && $user->is(constant($roleConstant))) {
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
        return request()->routeIs($route . '*') ? 'active' : '';
    }


}
