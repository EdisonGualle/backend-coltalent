<?php

namespace App\Http\Requests\Employee\Education;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingRequest extends FormRequest
{
    public function rules()
    {
        return [
            'institution' => 'required|string|max:255',
            'topic' => 'required|string|max:255',
            'year' => 'required|integer|min:1990|lte:' . date('Y'),
            'num_hours' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'attendance' => 'required|numeric|min:0|max:100',
            'approval' => 'nullable|string|max:255',
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
