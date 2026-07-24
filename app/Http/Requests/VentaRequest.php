<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fkalum' => 'required|exists:alumno,id_alumno',
            'fkmetodo' => 'required|exists:metodos_pago,id_metod',
            'venta_total' => 'required|numeric|min:0',
            'venta_descuento' => 'nullable|numeric|min:0',
            'estado_venta' => 'required|in:completado,reservado,incompleto',
            'venta_fecha' => 'nullable|date',
            'fkproducto' => 'nullable|exists:productos,id_productos',
        ];
    }

    public function messages(): array
    {
        return [
            'fkalum.required' => 'Debe seleccionar un alumno.',
            'fkalum.exists' => 'El alumno seleccionado no es válido.',
            'fkmetodo.required' => 'Debe seleccionar un método de pago.',
            'fkmetodo.exists' => 'El método de pago seleccionado no es válido.',
            'venta_total.required' => 'El total de la venta es obligatorio.',
            'venta_total.numeric' => 'El total debe ser un valor numérico.',
            'venta_total.min' => 'El total no puede ser negativo.',
            'venta_descuento.numeric' => 'El descuento debe ser un valor numérico.',
            'venta_descuento.min' => 'El descuento no puede ser negativo.',
            'estado_venta.required' => 'Debe seleccionar un estado de venta.',
            'estado_venta.in' => 'El estado de venta debe ser completado, reservado o incompleto.',
            'venta_fecha.date' => 'La fecha de la venta debe ser una fecha válida.',
            'fkproducto.exists' => 'El producto seleccionado no es válido.',
        ];
    }
}
