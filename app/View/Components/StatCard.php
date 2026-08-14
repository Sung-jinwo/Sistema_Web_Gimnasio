<?php

namespace App\View\Components;

use Illuminate\View\Component;

class StatCard extends Component
{
    public string $title;
    public string $value;
    public string $icon;
    public string $color;
    public ?string $subtitle;

    public function __construct(
        string $title,
        string $value,
        string $icon = 'fa-chart-line',
        string $color = 'blue',
        ?string $subtitle = null
    ) {
        $this->title = $title;
        $this->value = $value;
        $this->icon = $icon;
        $this->color = $color;
        $this->subtitle = $subtitle;
    }

    public function render()
    {
        return view('components.stat-card');
    }

    public function getColorClasses(): array
    {
        $colors = [
            'blue' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'border' => 'border-blue-500'],
            'green' => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'border' => 'border-green-500'],
            'yellow' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600', 'border' => 'border-yellow-500'],
            'red' => ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'border' => 'border-red-500'],
            'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'border' => 'border-purple-500'],
            'pink' => ['bg' => 'bg-pink-100', 'text' => 'text-pink-600', 'border' => 'border-pink-500'],
            'indigo' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600', 'border' => 'border-indigo-500'],
            'orange' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600', 'border' => 'border-orange-500'],
            'gray' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'border' => 'border-gray-500'],
        ];

        return $colors[$this->color] ?? $colors['blue'];
    }
}
