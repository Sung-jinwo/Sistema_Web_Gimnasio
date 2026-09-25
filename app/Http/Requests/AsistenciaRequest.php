<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AsistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo_documento' => 'required|string|max:20',
            'tipo_ingreso' => 'required|in:codigo,dni',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo_documento.required' => 'Debe ingresar el código o DNI del alumno.',
            'codigo_documento.max' => 'El código o DNI no puede superar 20 caracteres.',
            'tipo_ingreso.required' => 'Debe seleccionar el tipo de ingreso',
            'tipo_ingreso.in' => 'El tipo de ingreso debe ser código o DNI.',
        ];
    }
}
