<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Modal extends Component
{
    public string $title;
    public string $size;
    public bool $dismissible;

    public function __construct(
        string $title = '',
        string $size = 'md',
        bool $dismissible = true
    ) {
        $this->title = $title;
        $this->size = $size;
        $this->dismissible = $dismissible;
    }

    public function render(): View|Closure|string
    {
        return view('components.modal');
    }

    public function getSizeClass(): string
    {
        return match($this->size) {
            'sm' => 'max-w-sm',
            'md' => 'max-w-md',
            'lg' => 'max-w-lg',
            'xl' => 'max-w-xl',
            default => 'max-w-md',
        };
    }
}