<?php

namespace App\Http\Requests\Schedules;

use Illuminate\Foundation\Http\FormRequest;

class CreateDailyOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'date' => ['required', 'date', 'after:today'],
            'adjusted_start_time' => ['required', 'date_format:H:i:s', 'before:adjusted_end_time'],
            'adjusted_end_time' => ['required', 'date_format:H:i:s', 'after:adjusted_start_time'],
            'reason' => ['required', 'string', 'min:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'El ID del empleado es obligatorio.',
            'date.after' => 'La fecha debe ser futura.',
        ];
    }
}
