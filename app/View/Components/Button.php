<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Button extends Component
{
    /**
     * Variante del botón
     */
    public string $variant;

    /**
     * Tamaño del botón
     */
    public string $size;

    /**
     * Tipo de botón
     */
    public string $type;

    /**
     * Si el botón está deshabilitado
     */
    public bool $disabled;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $variant = 'primary',
        string $size = 'md',
        string $type = 'button',
        bool $disabled = false
    ) {
        $this->variant = $variant;
        $this->size = $size;
        $this->type = $type;
        $this->disabled = $disabled;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.button');
    }

    /**
     * Obtener clases de variante
     */
    public function getVariantClasses(): string
    {
        return match($this->variant) {
            'primary' => 'bg-purple-600 text-white hover:bg-purple-700 focus:ring-purple-500',
            'secondary' => 'bg-gray-200 text-gray-900 hover:bg-gray-300 focus:ring-gray-400',
            'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
            'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
            'warning' => 'bg-yellow-500 text-white hover:bg-yellow-600 focus:ring-yellow-400',
            'info' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
            'outline' => 'border border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-gray-400',
            'ghost' => 'text-gray-700 hover:bg-gray-100 focus:ring-gray-400',
            default => 'bg-purple-600 text-white hover:bg-purple-700 focus:ring-purple-500',
        };
    }

    /**
     * Obtener clases de tamaño
     */
    public function getSizeClasses(): string
    {
        return match($this->size) {
            'xs' => 'px-2 py-1 text-xs',
            'sm' => 'px-3 py-1.5 text-sm',
            'md' => 'px-4 py-2 text-sm',
            'lg' => 'px-5 py-2.5 text-base',
            'xl' => 'px-6 py-3 text-base',
            default => 'px-4 py-2 text-sm',
        };
    }
}