<?php

namespace App\Http\Requests\Work;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOvertimeWorkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'start_time' => [
                'nullable',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    if ($value && !$this->end_time) {
                        $fail('Si proporciona la hora de inicio, también debe proporcionar la hora de fin.');
                    }
                },
            ],
            'end_time' => [
                'nullable',
                'date_format:H:i',
                'after:start_time',
                function ($attribute, $value, $fail) {
                    if ($value && !$this->start_time) {
                        $fail('Si proporciona la hora de fin, también debe proporcionar la hora de inicio.');
                    }
                },
            ],
            'break_start_time' => [
                'nullable',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    if ($value && !$this->break_end_time) {
                        $fail('Si proporciona la hora de inicio del descanso, también debe proporcionar la hora de fin.');
                    }
                },
            ],
            'break_end_time' => [
                'nullable',
                'date_format:H:i',
                'after:break_start_time',
                function ($attribute, $value, $fail) {
                    if ($value && !$this->break_start_time) {
                        $fail('Si proporciona la hora de fin del descanso, también debe proporcionar la hora de inicio.');
                    }
                },
            ],
            'reason' => 'required|string|max:255',
            'generates_compensatory' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'El ID del empleado es obligatorio.',
            'employee_id.exists' => 'El empleado no existe.',
            'date.required' => 'La fecha del trabajo es obligatoria.',
            'date.date' => 'La fecha debe ser válida.',
            'start_time.date_format' => 'La hora de inicio debe estar en formato HH:MM.',
            'end_time.date_format' => 'La hora de fin debe estar en formato HH:MM.',
            'end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'break_start_time.date_format' => 'La hora de inicio del descanso debe estar en formato HH:MM.',
            'break_end_time.date_format' => 'La hora de fin del descanso debe estar en formato HH:MM.',
            'break_end_time.after' => 'La hora de fin del descanso debe ser posterior a la hora de inicio.',
            'reason.required' => 'El motivo es obligatorio.',
        ];
    }
}
