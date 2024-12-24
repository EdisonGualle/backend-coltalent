<?php

namespace App\Http\Requests\Holidays;

use Illuminate\Foundation\Http\FormRequest;

class CreateHolidayAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_ids.required' => 'Debes proporcionar al menos un empleado.',
            'employee_ids.array' => 'El formato de empleados debe ser una lista.',
            'employee_ids.*.exists' => 'Uno o más empleados no existen en el sistema.',
        ];
    }
}
