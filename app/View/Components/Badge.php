<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Badge extends Component
{
    /**
     * Variante del badge
     */
    public string $variant;

    /**
     * Tamaño del badge
     */
    public string $size;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $variant = 'default',
        string $size = 'md'
    ) {
        $this->variant = $variant;
        $this->size = $size;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.badge');
    }

    /**
     * Obtener clases de variante
     */
    public function getVariantClasses(): string
    {
        return match ($this->variant) {
            'success' => 'bg-green-100 text-green-800',
            'warning' => 'bg-yellow-100 text-yellow-800',
            'danger' => 'bg-red-100 text-red-800',
            'info' => 'bg-blue-100 text-blue-800',
            'default' => 'bg-gray-100 text-gray-800',
            'primary' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Obtener clases de tamaño
     */
    public function getSizeClasses(): string
    {
        return match ($this->size) {
            'sm' => 'px-2 py-0.5 text-xs',
            'md' => 'px-3 py-1 text-xs',
            'lg' => 'px-4 py-1.5 text-sm',
            default => 'px-3 py-1 text-xs',
        };
    }
}
