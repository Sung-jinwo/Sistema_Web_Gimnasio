<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Table extends Component
{

    public array $headers;


    public bool $hoverable;


    public bool $bordered;

    public function __construct(
        array $headers = [],
        bool $hoverable = true,
        bool $bordered = true
    ) {
        $this->headers = $headers;
        $this->hoverable = $hoverable;
        $this->bordered = $bordered;
    }

    public function render(): View|Closure|string
    {
        return view('components.table');
    }
}