<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ModalForm extends Component
{
    /**
     * Título del modal
     */
    public string $title;

    /**
     * Subtítulo o descripción
     */
    public ?string $subtitle;

    /**
     * Tamaño del modal
     */
    public string $size;

    /**
     * Icono del header
     */
    public ?string $icon;

    /**
     * Color del header (gradient)
     */
    public string $headerColor;

    /**
     * Si tiene scroll interno
     */
    public bool $scrollable;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $title = '',
        ?string $subtitle = null,
        string $size = '2xl',
        ?string $icon = null,
        string $headerColor = 'purple',
        bool $scrollable = true
    ) {
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->size = $size;
        $this->icon = $icon;
        $this->headerColor = $headerColor;
        $this->scrollable = $scrollable;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.modal-form');
    }

    /**
     * Obtener clase CSS del tamaño
     */
    public function getSizeClass(): string
    {
        return match ($this->size) {
            'sm' => 'max-w-sm',
            'md' => 'max-w-md',
            'lg' => 'max-w-lg',
            'xl' => 'max-w-xl',
            '2xl' => 'max-w-2xl',
            '3xl' => 'max-w-3xl',
            '4xl' => 'max-w-4xl',
            '5xl' => 'max-w-5xl',
            'full' => 'max-w-full',
            default => 'max-w-2xl',
        };
    }

    /**
     * Obtener color del header
     */
    public function getHeaderColorClass(): string
    {
        return match ($this->headerColor) {
            'purple' => 'from-purple-600 to-purple-700',
            'blue' => 'from-blue-600 to-blue-700',
            'green' => 'from-green-600 to-green-700',
            'red' => 'from-red-600 to-red-700',
            'orange' => 'from-orange-600 to-orange-700',
            'indigo' => 'from-indigo-600 to-indigo-700',
            default => 'from-purple-600 to-purple-700',
        };
    }
}
