<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;


class IconButton extends Button
{
    public string $icon;
    
    public function __construct(
        string $icon,
        string $variant = 'primary',
        string $size = 'md'
    ) {
        parent::__construct($variant, $size);
        $this->icon = $icon;
    }
}