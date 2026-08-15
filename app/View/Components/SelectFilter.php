<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SelectFilter extends Component
{
    /**
     * Opciones del select
     */
    public array $options;

    /**
     * Modelo de Alpine.js
     */
    public string $model;

    /**
     * Label por defecto
     */
    public string $defaultLabel;

    /**
     * Create a new component instance.
     */
    public function __construct(
        array $options = [],
        string $model = 'filter',
        string $defaultLabel = 'Todos'
    ) {
        $this->options = $options;
        $this->model = $model;
        $this->defaultLabel = $defaultLabel;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.select-filter');
    }
}
