<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prod_nombre' => 'required',
            'prod_precio' => 'required|numeric|min:0.01',
            'prod_cantidad' => 'required|integer|min:0',
            'fkcategoria' => 'required|exists:categorias,id_categoria',
        ];
    }

    public function messages(): array
    {
        return [
            'prod_nombre.required' => 'El nombre del producto es requerido',
            'prod_precio.required' => 'El precio es requerido',
            'prod_precio.numeric' => 'El precio debe ser un número',
            'prod_precio.min' => 'El precio debe ser al menos 0.01',
            'prod_cantidad.required' => 'La cantidad es requerida',
            'prod_cantidad.integer' => 'La cantidad debe ser un número entero',
            'prod_cantidad.min' => 'La cantidad no puede ser negativa',
            'fkcategoria.required' => 'Debe seleccionar una categoría',
            'fkcategoria.exists' => 'La categoría seleccionada no existe',
        ];
    }
}
