<?php

namespace App\Http\Requests\Holidays;

use Illuminate\Foundation\Http\FormRequest;

class DeleteHolidayAssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignment_ids' => 'required|array|min:1',
            'assignment_ids.*' => 'exists:holiday_assignments,id',
        ];
    }

    public function messages(): array
    {
        return [
            'assignment_ids.required' => 'Debes proporcionar al menos una asignación.',
            'assignment_ids.array' => 'El formato de asignaciones debe ser una lista.',
            'assignment_ids.*.exists' => 'Una o más asignaciones no existen en el sistema.',
        ];
    }
}
