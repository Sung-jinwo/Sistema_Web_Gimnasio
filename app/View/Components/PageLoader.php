<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PageLoader extends Component
{
    /**
     * Mensaje del loader
     */
    public string $message;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $message = 'Cargando datos...'
    ) {
        $this->message = $message;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.page-loader');
    }
}