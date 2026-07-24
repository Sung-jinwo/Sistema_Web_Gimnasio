<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SearchInput extends Component
{

    public string $placeholder;


    public string $model;


    public function __construct(
        string $placeholder = 'Buscar...',
        string $model = 'searchTerm'
    ) {
        $this->placeholder = $placeholder;
        $this->model = $model;
    }


    public function render(): View|Closure|string
    {
        return view('components.search-input');
    }
}