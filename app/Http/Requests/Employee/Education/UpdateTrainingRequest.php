<?php

namespace App\Http\Requests\Employee\Education;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainingRequest extends FormRequest
{
    public function rules()
    {
        return [
            'institution' => 'string|max:150',
            'topic' => 'string|max:255',
            'year' => 'integer|min:1990|lte:' . date('Y'),
            'num_hours' => 'integer|min:1|max:1000',
            'training_type_id' => 'exists:training_types,id',
            'employee_id' => 'exists:employees,id',
        ];
    }

    public function messages()
    {
        return [
            'institution.string' => 'La institución debe ser una cadena de texto.',
            'institution.max' => 'La institución no debe exceder los 255 caracteres.',
            'topic.string' => 'El tema debe ser una cadena de texto.',
            'topic.max' => 'El tema no debe exceder los 255 caracteres.',
            'year.integer' => 'El año debe ser un número entero.',
            'year.min' => 'El año debe ser mayor o igual a 1990.',
            'year.lte' => 'El año no puede ser mayor que el año actual.',
            'num_hours.integer' => 'El número de horas debe ser un número entero.',
            'num_hours.min' => 'El número de horas debe ser un valor positivo.',
            'num_hours.max' => 'El número de horas no debe exceder los 1000.',
            'training_type_id.exists' => 'El tipo de capacitación seleccionado no es válido.',
            'employee_id.exists' => 'El ID del empleado seleccionado no es válido.',
        ];
    }
}
