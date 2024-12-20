<?php

namespace App\Http\Requests\Schedules;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Schedules\EmployeeSchedule;

class ChangeEmployeeScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_id' => [
                'required',
                Rule::exists('schedules', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'schedule_id.required' => 'El horario es obligatorio.',
            'schedule_id.exists' => 'El horario seleccionado no existe.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $employeeId = $this->route('employee_id');
            $scheduleId = $this->input('schedule_id');

            $currentAssignment = EmployeeSchedule::where('employee_id', $employeeId)
                ->where('is_active', true)
                ->first();

            if ($currentAssignment && $currentAssignment->schedule_id == $scheduleId) {
                $validator->errors()->add('schedule_id', 'El nuevo horario no puede ser igual al horario actual.');
            }
        });
    }
}
