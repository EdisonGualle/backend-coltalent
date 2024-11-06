<?php
namespace App\Http\Requests\Employee\Schedules;

use Illuminate\Foundation\Http\FormRequest;

class CreateWorkScheduleRequest extends FormRequest
{
    public function rules()
    {
        return [
            '*.day_of_week' => 'required|integer|min:1|max:7',
            '*.start_time' => 'required|date_format:H:i',
            '*.end_time' => 'required|date_format:H:i|after:start_time',
            '*.has_lunch_break' => 'required|boolean',
            '*.lunch_start_time' => 'nullable|required_if:*.has_lunch_break,true|date_format:H:i',
            '*.lunch_end_time' => 'nullable|required_if:*.has_lunch_break,true|date_format:H:i|after:lunch_start_time',
        ];
    }

    public function messages()
    {
        return [
            '*.day_of_week.required' => 'El día de la semana es obligatorio.',
            '*.day_of_week.integer' => 'El día de la semana debe ser un número entero entre 1 y 7.',
            '*.start_time.required' => 'La hora de inicio es obligatoria.',
            '*.start_time.date_format' => 'La hora de inicio debe estar en el formato HH:MM.',
            '*.end_time.required' => 'La hora de fin es obligatoria.',
            '*.end_time.date_format' => 'La hora de fin debe estar en el formato HH:MM.',
            '*.end_time.after' => 'La hora de fin debe ser después de la hora de inicio.',
            '*.has_lunch_break.required' => 'Es necesario especificar si hay descanso para almuerzo.',
            '*.has_lunch_break.boolean' => 'El valor de descanso para almuerzo debe ser verdadero o falso.',
            '*.lunch_start_time.required_if' => 'La hora de inicio del almuerzo es obligatoria cuando se indica que hay descanso para almuerzo.',
            '*.lunch_start_time.date_format' => 'La hora de inicio del almuerzo debe estar en el formato HH:MM.',
            '*.lunch_end_time.required_if' => 'La hora de fin del almuerzo es obligatoria cuando se indica que hay descanso para almuerzo.',
            '*.lunch_end_time.date_format' => 'La hora de fin del almuerzo debe estar en el formato HH:MM.',
            '*.lunch_end_time.after' => 'La hora de fin del almuerzo debe ser después de la hora de inicio del almuerzo.',
        ];
    }
}
