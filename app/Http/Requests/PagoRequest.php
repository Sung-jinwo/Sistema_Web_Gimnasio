<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fkalum' => 'required|exists:alumno,id_alumno',
            'fkmem' => 'required|exists:membresias,id_mem',
            'fkmetodo' => 'required|exists:metodos_pago,id_metod',
            'pag_inicio' => 'required|date',
            'pag_fin' => 'required|date|after_or_equal:pag_inicio',
            'pag_monto' => 'required|numeric|min:0',
            'estado_pago' => 'required|in:completo,incompleto,reservado',
        ];
    }

    public function messages(): array
    {
        return [
            'fkalum.required' => 'Debe seleccionar un alumno',
            'fkalum.exists' => 'El alumno seleccionado no existe',
            'fkmem.required' => 'Debe seleccionar una membresía',
            'fkmem.exists' => 'La membresía seleccionada no existe',
            'fkmetodo.required' => 'Debe seleccionar un método de pago',
            'fkmetodo.exists' => 'El método de pago seleccionado no existe',
            'pag_inicio.required' => 'La fecha de inicio es requerida',
            'pag_inicio.date' => 'La fecha de inicio debe ser una fecha válida',
            'pag_fin.required' => 'La fecha de fin es requerida',
            'pag_fin.date' => 'La fecha de fin debe ser una fecha válida',
            'pag_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio',
            'pag_monto.required' => 'El monto es requerido',
            'pag_monto.numeric' => 'El monto debe ser un número',
            'pag_monto.min' => 'El monto no puede ser negativo',
            'estado_pago.required' => 'El estado del pago es requerido',
            'estado_pago.in' => 'El estado debe ser completo, incompleto o reservado',
        ];
    }
}
