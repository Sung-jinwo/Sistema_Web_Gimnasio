<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AbonoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto' => ['required', 'numeric', 'min:0.01'],
            'fkmetodo' => ['required', 'exists:metodos_pago,id_metod'],
            'fecha_abono' => ['nullable', 'date', 'before_or_equal:today'],
            'num_comprobante' => ['nullable', 'string', 'max:50'],
            'observacion' => ['nullable', 'string', 'max:500'],
        ];
    }
}
