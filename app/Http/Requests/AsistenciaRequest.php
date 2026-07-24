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
            'fkalum' => 'required|exists:alumno,id_alumno',
            'tipo_ingreso' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'fkalum.required' => 'Debe seleccionar un alumno',
            'fkalum.exists' => 'El alumno seleccionado no existe',
            'tipo_ingreso.required' => 'Debe seleccionar el tipo de ingreso',
        ];
    }
}
