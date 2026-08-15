<?php

namespace App\Http\Requests;

use App\Models\Alumno;
use Illuminate\Foundation\Http\FormRequest;

class AlumnoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('alum_codigo')) {
            do {
                $codigo = (string) random_int(1000, 9999);
            } while (Alumno::withTrashed()->where('alum_codigo', $codigo)->exists());

            $this->merge(['alum_codigo' => $codigo]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $alumno = $this->route('alumno');
        $alumnoId = null;

        if (is_object($alumno)) {
            $alumnoId = $alumno->id_alumno;
        } elseif (is_numeric($alumno)) {
            $alumnoId = $alumno;
        }

        return [
            'alum_codigo' => 'required|string|max:20|unique:alumno,alum_codigo,'.$alumnoId.',id_alumno',
            'alum_nombre' => 'required|string|max:100',
            'alum_apellido' => 'required|string|max:100',
            'alum_direccion' => 'nullable|string|max:200',
            'alum_correro' => 'nullable|email|max:100',
            'alum_telefo' => 'required|string|max:20',
            'alum_numDoc' => 'required|string|max:20|unique:alumno,alum_numDoc,'.$alumnoId.',id_alumno'.($this->input('alum_documento') === 'DNI' ? '|size:8' : ''),
            'alum_documento' => 'required|in:DNI,CE,PAS,OTRO',
            'fksexo' => 'required|exists:sexo,id_sexo',
            'fksede' => 'required|exists:sedes,id_sede',
            'fecha_nac' => 'required|date|before_or_equal:today',
            'alum_condi' => 'nullable|string',
            'alum_estado' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'alum_codigo.required' => 'El código del alumno es obligatorio.',
            'alum_codigo.unique' => 'El código del alumno ya está registrado.',
            'alum_codigo.max' => 'El código no puede exceder 20 caracteres.',
            'alum_nombre.required' => 'El nombre del alumno es obligatorio.',
            'alum_nombre.max' => 'El nombre no puede exceder 100 caracteres.',
            'alum_apellido.required' => 'El apellido del alumno es obligatorio.',
            'alum_apellido.max' => 'El apellido no puede exceder 100 caracteres.',
            'alum_numDoc.required' => 'El DNI del alumno es obligatorio.',
            'alum_numDoc.size' => 'El DNI debe tener exactamente 8 dígitos.',
            'alum_numDoc.unique' => 'El DNI ya está registrado.',
            'alum_documento.required' => 'El tipo de documento es obligatorio.',
            'alum_documento.in' => 'El tipo de documento debe ser DNI, CE o PAS.',
            'fksexo.required' => 'Debe seleccionar el sexo del alumno.',
            'fksexo.exists' => 'El sexo seleccionado no es válido.',
            'fecha_nac.required' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nac.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            'fecha_nac.before_or_equal' => 'La fecha de nacimiento no puede ser una fecha futura.',
            'alum_telefo.required' => 'El teléfono del alumno es obligatorio.',
            'alum_telefo.max' => 'El teléfono no puede exceder 20 caracteres.',
            'alum_correro.email' => 'El correo electrónico debe tener un formato válido.',
            'alum_correro.max' => 'El correo no puede exceder 100 caracteres.',
            'fksede.required' => 'Debe seleccionar una sede.',
            'fksede.exists' => 'La sede seleccionada no es válida.',
        ];
    }
}
