<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');
        $id = null;

        if (is_object($usuario)) {
            $id = $usuario->id;
        } elseif (is_numeric($usuario)) {
            $id = $usuario;
        }

        $rules = [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email'.($id ? ','.$id : ''),
            'password' => 'required|min:6',
            'role' => 'required|string|exists:roles,name',
            'fksede' => 'required|exists:sedes,id_sede',
            'telefono' => 'nullable|string|max:20',
        ];

        if ($id) {
            $rules['password'] = 'nullable|min:6';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del usuario es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo electrónico válido.',
            'email.unique' => 'El correo electrónico ya se encuentra registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'role.required' => 'Debe seleccionar un rol.',
            'role.exists' => 'El rol seleccionado no es válido.',
            'fksede.required' => 'Debe seleccionar una sede.',
            'fksede.exists' => 'La sede seleccionada no es válida.',
            'telefono.string' => 'El teléfono debe ser texto.',
            'telefono.max' => 'El teléfono no puede superar los 20 caracteres.',
        ];
    }
}
