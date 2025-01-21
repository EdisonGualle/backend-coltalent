<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class CreateUnitRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:150|unique:units,name',
            'function' => 'required|string|max:255',
            'phone' => 'nullable|digits:9',
            'direction_id' => 'required|exists:directions,id',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'El nombre de la unidad es obligatorio.',
            'name.string' => 'El nombre de la unidad debe ser una cadena de texto.',
            'name.max' => 'El nombre de la unidad no puede tener más de 150 caracteres.',
            'name.unique' => 'Ya existe una unidad con ese nombre.',
            'function.required' => 'La función de la unidad es obligatoria.',
            'function.string' => 'La función de la unidad debe ser una cadena de texto.',
            'function.max' => 'La función de la unidad no puede tener más de 255 caracteres.',
            'phone.digits' => 'El teléfono de la unidad debe tener 9 dígitos.',
            'direction_id.required' => 'El ID de la dirección es obligatorio.',
            'direction_id.exists' => 'La dirección seleccionada no existe.',
        ];
    }
}
