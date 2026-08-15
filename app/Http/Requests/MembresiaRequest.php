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
            'mem_nombre' => 'required|string|max:100',
            'mem_precio' => 'required|numeric|min:0',
            'comision' => 'nullable|numeric|min:0|max:100',
            'modalidad' => 'required|in:por_duracion,por_meses,por_fechas',
            'mem_duracion' => 'required_unless:modalidad,por_fechas|nullable|integer|min:1',
            'fecha_inicio_fija' => 'required_if:modalidad,por_fechas|nullable|date',
            'fecha_fin_fija' => 'required_if:modalidad,por_fechas|nullable|date|after_or_equal:fecha_inicio_fija',
            'mem_categoria' => 'required|in:Regular,Premium,VIP',
            'mem_tipo' => 'required|in:Diaria,Semanal,Mensual,Trimestral,Semestral,Anual',
            'mem_beneficios' => 'nullable|string',
            'estado' => 'nullable|in:A,I',
        ];
    }

    public function messages(): array
    {
        return [
            'mem_nombre.required' => 'El nombre de la membresía es requerido.',
            'mem_nombre.max' => 'El nombre no puede exceder 100 caracteres.',
            'mem_precio.required' => 'El precio es requerido.',
            'mem_precio.numeric' => 'El precio debe ser un número.',
            'mem_precio.min' => 'El precio no puede ser negativo.',
            'comision.numeric' => 'La comisión debe ser un número.',
            'comision.min' => 'La comisión no puede ser negativa.',
            'comision.max' => 'La comisión no puede exceder 100%.',
            'modalidad.required' => 'La modalidad es requerida.',
            'modalidad.in' => 'La modalidad debe ser por_meses o por_fechas.',
            'mem_duracion.required_if' => 'La duración es requerida cuando la modalidad es por meses.',
            'mem_duracion.integer' => 'La duración debe ser un número entero.',
            'mem_duracion.min' => 'La duración debe ser al menos 1 día.',
            'mem_categoria.required' => 'La categoría es requerida.',
            'mem_categoria.in' => 'La categoría debe ser Regular, Premium o VIP.',
            'mem_tipo.required' => 'El tipo es requerido.',
            'mem_tipo.in' => 'El tipo debe ser Diaria, Semanal, Mensual, Trimestral, Semestral o Anual.',
        ];
    }
}
