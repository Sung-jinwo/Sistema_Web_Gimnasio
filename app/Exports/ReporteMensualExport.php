<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReporteMensualExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(
        private readonly array $filas,
        private readonly array $encabezados
    ) {}

    public function collection()
    {
        return collect($this->filas);
    }

    public function headings(): array
    {
        return $this->encabezados;
    }
}
