<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDirectionRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'string|max:255|unique:directions,name,' . $this->route('direction'),
            'function' => 'string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'name.unique' => 'Ya existe una dirección con ese nombre.',
            'function.string' => 'La función debe ser una cadena de texto.',
            'function.max' => 'La función no puede tener más de 255 caracteres.',
        ];
    }
}
