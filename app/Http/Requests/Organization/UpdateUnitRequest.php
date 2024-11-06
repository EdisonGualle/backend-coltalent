<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'string|max:150|unique:units,name,' . $this->route('unit'),
            'function' => 'string|max:255',
            'phone' => 'nullable|digits:9',
            'direction_id' => 'exists:directions,id',
        ];
    }

    public function messages()
    {
        return [
            'name.string' => 'El nombre de la unidad debe ser una cadena de texto.',
            'name.max' => 'El nombre de la unidad no puede tener más de 150 caracteres.',
            'name.unique' => 'Ya existe una unidad con ese nombre.',
            'function.string' => 'La función de la unidad debe ser una cadena de texto.',
            'function.max' => 'La función de la unidad no puede tener más de 255 caracteres.',
            'phone.digits' => 'El teléfono de la unidad debe tener 9 dígitos.',
            'direction_id.exists' => 'La dirección seleccionada no existe.',
        ];
    }
}
