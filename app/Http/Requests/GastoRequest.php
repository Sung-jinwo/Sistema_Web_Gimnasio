<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GastoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'gas_concepto' => 'required|string|max:200',
            'gas_monto' => 'required|numeric|min:0.01',
            'gas_fecha' => 'required|date',
            'fkcategoria' => 'nullable|exists:categorias_gasto,id_categoria',
            'gas_observacion' => 'nullable|string',
        ];

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['motivo_rechazo'] = 'required_if:estado,rechazado|nullable|string|max:500';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'gas_concepto.required' => 'El concepto del gasto es obligatorio.',
            'gas_concepto.string' => 'El concepto debe ser texto.',
            'gas_concepto.max' => 'El concepto no puede superar los 200 caracteres.',
            'gas_monto.required' => 'El monto del gasto es obligatorio.',
            'gas_monto.numeric' => 'El monto debe ser un valor numérico.',
            'gas_monto.min' => 'El monto debe ser al menos 0.01.',
            'gas_fecha.required' => 'La fecha del gasto es obligatoria.',
            'gas_fecha.date' => 'La fecha debe ser una fecha válida.',
            'fkcategoria.exists' => 'La categoría de gasto seleccionada no es válida.',
            'gas_observacion.string' => 'La observación debe ser texto.',
            'motivo_rechazo.required_if' => 'Debe ingresar un motivo para rechazar el gasto.',
            'motivo_rechazo.string' => 'El motivo debe ser texto.',
            'motivo_rechazo.max' => 'El motivo no puede superar los 500 caracteres.',
        ];
    }
}
