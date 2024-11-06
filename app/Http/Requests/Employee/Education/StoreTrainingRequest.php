<?php

namespace App\Http\Requests\Employee\Education;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingRequest extends FormRequest
{
    public function rules()
    {
        return [
            'institution' => 'required|string|max:150',
            'topic' => 'required|string|max:255',
            'year' => 'required|integer|min:1990|lte:' . date('Y'),
            'num_hours' => 'required|integer|min:1|max:1000',
            'training_type_id' => 'required|exists:training_types,id',
        ];
    }

    public function messages()
    {
        return [
            'institution.required' => 'La institución es obligatoria.',
            'institution.string' => 'La institución debe ser una cadena de texto.',
            'institution.max' => 'La institución no debe exceder los 150 caracteres.',
            'topic.required' => 'El tema es obligatorio.',
            'topic.string' => 'El tema debe ser una cadena de texto.',
            'topic.max' => 'El tema no debe exceder los 255 caracteres.',
            'year.required' => 'El año es obligatorio.',
            'year.integer' => 'El año debe ser un número entero.',
            'year.min' => 'El año debe ser mayor o igual a 1990.',
            'year.lte' => 'El año no puede ser mayor que el año actual.',
            'num_hours.required' => 'El número de horas es obligatorio.',
            'num_hours.integer' => 'El número de horas debe ser un número entero.',
            'num_hours.min' => 'El número de horas debe ser un valor positivo.',
            'num_hours.max' => 'El número de horas no debe exceder los 1000.',
            'training_type_id.required' => 'El tipo de capacitación es obligatorio.',
            'training_type_id.exists' => 'El tipo de capacitación seleccionado no es válido.',
        ];
    }
}
