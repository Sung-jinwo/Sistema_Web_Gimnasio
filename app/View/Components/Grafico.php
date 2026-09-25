<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Grafico extends Component
{
    /**
     * @param  array<int, array{etiqueta: string, datos: array<int, float|int>, color?: string, fondo?: string}>  $series
     */
    public function __construct(
        public string $titulo = '',
        public string $subtitulo = '',
        public string $tipo = 'bar',
        public array $etiquetas = [],
        public array $series = [],
        public bool $moneda = false,
        public string $altura = '280px',
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.grafico');
    }

    /**
     * True cuando todas las series están en cero o vacías.
     */
    public function vacio(): bool
    {
        foreach ($this->series as $serie) {
            foreach ($serie['datos'] ?? [] as $dato) {
                if ((float) $dato != 0) {
                    return false;
                }
            }
        }

        return true;
    }

    public function config(): array
    {
        $esDona = $this->tipo === 'doughnut' || $this->tipo === 'pie';

        return [
            'type' => $this->tipo,
            'data' => [
                'labels' => $this->etiquetas,
                'datasets' => collect($this->series)->map(fn ($s, $i) => [
                    'label' => $s['etiqueta'] ?? '',
                    'data' => $s['datos'] ?? [],
                    'backgroundColor' => $esDona
                        ? array_map(fn ($j) => self::PALETA[$j % count(self::PALETA)], array_keys($s['datos'] ?? []))
                        : ($s['fondo'] ?? self::PALETA[$i % count(self::PALETA)].'B3'),
                    'borderColor' => $esDona
                        ? '#ffffff'
                        : ($s['color'] ?? self::PALETA[$i % count(self::PALETA)]),
                    'borderWidth' => $esDona ? 2 : 1,
                    'borderRadius' => $esDona ? 0 : 6,
                ])->all(),
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => $esDona ? 'bottom' : 'top'],
                ],
                'scales' => $esDona ? new \stdClass : [
                    'y' => ['beginAtZero' => true],
                ],
            ],
        ];
    }

    public const PALETA = ['#ec4899', '#8b5cf6', '#10b981', '#f59e0b', '#3b82f6', '#ef4444'];
}
