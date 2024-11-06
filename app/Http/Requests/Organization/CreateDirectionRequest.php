<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class CreateDirectionRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:150|unique:directions,name',
            'function' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'El nombre es requerido.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede tener más de 150 caracteres.',
            'name.unique' => 'Ya existe una dirección con ese nombre.',
            'function.required' => 'La función es requerida.',
            'function.string' => 'La función debe ser una cadena de texto.',
            'function.max' => 'La función no puede tener más de 255 caracteres.',
        ];
    }
}
