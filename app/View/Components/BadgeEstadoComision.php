<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BadgeEstadoComision extends Component
{
    public function __construct(public string $estado = '') {}

    public function render(): View|Closure|string
    {
        return view('components.badge-estado-comision');
    }

    public function clases(): string
    {
        return match ($this->estado) {
            'esperando_pago' => 'bg-gray-100 text-gray-800',
            'pendiente_revision' => 'bg-yellow-100 text-yellow-800',
            'aprobada' => 'bg-blue-100 text-blue-800',
            'observada' => 'bg-red-100 text-red-800',
            'liquidada' => 'bg-green-100 text-green-800',
            'anulada' => 'bg-gray-200 text-gray-500 line-through',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function etiqueta(): string
    {
        return match ($this->estado) {
            'esperando_pago' => 'Esperando pago',
            'pendiente_revision' => 'Pendiente de revisión',
            'aprobada' => 'Aprobada',
            'observada' => 'Observada',
            'liquidada' => 'Liquidada',
            'anulada' => 'Anulada',
            default => ucfirst($this->estado),
        };
    }
}
