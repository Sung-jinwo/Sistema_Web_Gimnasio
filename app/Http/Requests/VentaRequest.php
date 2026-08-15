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
        $rules = [
            'tipo_venta' => 'required|in:membresia,producto,rapida',
            'fkmetodo' => 'required|exists:metodos_pago,id_metod',
            'venta_descuento' => 'nullable|numeric|min:0',
            'estado_venta' => 'nullable|in:completado,reservado',
            'estado_pago' => 'nullable|in:pagado,parcial,pendiente',
            'monto_pagado' => 'nullable|numeric|min:0',
            'fecha_acordada' => 'nullable|date|after_or_equal:today',
            'venta_fecha' => 'nullable|date',
            'observacion' => 'nullable|string',
            'cantidad' => 'nullable|integer|min:1',
        ];

        if ($this->input('tipo_venta') === 'rapida') {
            $rules['detalles'] = 'required_without:fkproducto|array|min:1';
            $rules['detalles.*.fkproducto'] = 'required|exists:productos,id_productos';
            $rules['detalles.*.cantidad'] = 'required|integer|min:1';
            $rules['fkproducto'] = 'required_without:detalles|exists:productos,id_productos';
        } elseif ($this->input('tipo_venta') === 'producto') {
            $rules['fkalum'] = 'required|exists:alumno,id_alumno';
            $rules['detalles'] = 'required_without:fkproducto|array|min:1';
            $rules['detalles.*.fkproducto'] = 'required|exists:productos,id_productos';
            $rules['detalles.*.cantidad'] = 'required|integer|min:1';
            $rules['fkproducto'] = 'required_without:detalles|exists:productos,id_productos';
        } elseif ($this->input('tipo_venta') === 'membresia') {
            $rules['fkalum'] = 'required|exists:alumno,id_alumno';
            $rules['fkmem'] = 'required|exists:membresias,id_mem';
            $rules['fecha_inicio'] = 'nullable|date';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'tipo_venta.required' => 'Debe seleccionar un tipo de venta.',
            'tipo_venta.in' => 'El tipo de venta debe ser membresia, producto o rapida.',
            'fkalum.required' => 'Debe seleccionar un alumno.',
            'fkalum.exists' => 'El alumno seleccionado no es válido.',
            'fkmem.required' => 'Debe seleccionar una membresía.',
            'fkmem.exists' => 'La membresía seleccionada no es válida.',
            'fkproducto.required' => 'Debe seleccionar un producto.',
            'fkproducto.exists' => 'El producto seleccionado no es válido.',
            'fkmetodo.required' => 'Debe seleccionar un método de pago.',
            'fkmetodo.exists' => 'El método de pago seleccionado no es válido.',
            'cantidad.required' => 'Debe ingresar la cantidad.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad debe ser al menos 1.',
            'modalidad.required' => 'Debe seleccionar una modalidad.',
            'modalidad.in' => 'La modalidad debe ser por_meses o por_fechas.',
            'fecha_inicio.required_if' => 'La fecha de inicio es obligatoria para modalidad por meses.',
            'fecha_fin.required_if' => 'La fecha de fin es obligatoria para modalidad por fechas.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'venta_descuento.numeric' => 'El descuento debe ser un valor numérico.',
            'venta_descuento.min' => 'El descuento no puede ser negativo.',
            'estado_venta.required' => 'Debe seleccionar un estado de venta.',
            'estado_venta.in' => 'El estado de venta debe ser completado, reservado o incompleto.',
            'estado_pago.required' => 'Debe seleccionar un estado de pago.',
            'estado_pago.in' => 'El estado de pago debe ser pagado, parcial o pendiente.',
            'monto_pagado.numeric' => 'El monto pagado debe ser un valor numérico.',
            'monto_pagado.min' => 'El monto pagado no puede ser negativo.',
            'fecha_acordada.date' => 'La fecha acordada debe ser una fecha válida.',
            'fecha_acordada.after_or_equal' => 'La fecha acordada debe ser hoy o posterior.',
            'venta_fecha.date' => 'La fecha de la venta debe ser una fecha válida.',
        ];
    }
}
