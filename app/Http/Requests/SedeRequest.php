<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SedeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sede_nombre' => 'required|string|max:100',
            'sede_direccion' => 'nullable|string|max:200',
            'sede_telefono' => 'nullable|string|max:20',
            'sede_responsable' => 'nullable|string|max:100',
            'sede_horario' => 'nullable|string|max:100',
            'sede_estado' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'sede_nombre.required' => 'El nombre de la sede es obligatorio.',
            'sede_nombre.max' => 'El nombre no puede exceder 100 caracteres.',
            'sede_direccion.max' => 'La dirección no puede exceder 200 caracteres.',
            'sede_telefono.max' => 'El teléfono no puede exceder 20 caracteres.',
            'sede_responsable.max' => 'El responsable no puede exceder 100 caracteres.',
            'sede_horario.max' => 'El horario no puede exceder 100 caracteres.',
        ];
    }
}
