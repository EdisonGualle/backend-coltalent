<?php

namespace App\Http\Requests\Employee\Education;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainingRequest extends FormRequest
{
    public function rules()
    {
        return [
            'institution' => 'string|max:255',
            'topic' => 'string|max:255',
            'year' => 'integer|min:1990|lte:' . date('Y'),
            'num_hours' => 'integer|min:0|max:1000',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'attendance' => 'numeric|min:0|max:100',
            'approval' => 'string|max:255',
        ];
    }
    public function messages()
    {
        return [
            'year.min' => 'El año debe ser mayor o igual a 1990.',
            'year.lte' => 'El año no puede ser mayor que el año actual.',
            'num_hours.min' => 'El número de horas debe ser un valor positivo.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'end_date.required' => 'La fecha de finalización es obligatoria.',
            'end_date.after' => 'La fecha de finalización debe ser posterior a la fecha de inicio.',
            'attendance.min' => 'La asistencia no puede ser negativa.',
            'attendance.max' => 'La asistencia no puede ser mayor al 100%.',
        ];
    }
}
