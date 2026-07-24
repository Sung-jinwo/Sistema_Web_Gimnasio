<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MembresiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mem_nombre' => 'required',
            'mem_precio' => 'required|numeric|min:0',
            'mem_duracion' => 'required|integer|min:1',
            'mem_categoria' => 'required|in:Regular,Premium,VIP',
            'mem_tipo' => 'required|in:Diaria,Semanal,Mensual,Trimestral,Semestral,Anual',
        ];
    }

    public function messages(): array
    {
        return [
            'mem_nombre.required' => 'El nombre de la membresía es requerido',
            'mem_precio.required' => 'El precio es requerido',
            'mem_precio.numeric' => 'El precio debe ser un número',
            'mem_precio.min' => 'El precio no puede ser negativo',
            'mem_duracion.required' => 'La duración es requerida',
            'mem_duracion.integer' => 'La duración debe ser un número entero',
            'mem_duracion.min' => 'La duración debe ser al menos 1',
            'mem_categoria.required' => 'La categoría es requerida',
            'mem_categoria.in' => 'La categoría debe ser Regular, Premium o VIP',
            'mem_tipo.required' => 'El tipo es requerido',
            'mem_tipo.in' => 'El tipo debe ser Diaria, Semanal, Mensual, Trimestral, Semestral o Anual',
        ];
    }
}
