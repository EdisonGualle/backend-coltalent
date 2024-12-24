<?php

namespace App\Http\Requests\Holidays;

use Illuminate\Foundation\Http\FormRequest;

class CreateHolidayWorkRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'holiday_ids' => 'required|array|min:1',
            'holiday_ids.*' => 'exists:holidays,id',
            'type' => 'required|in:completo,horas',
            'worked_value' => 'required|numeric|min:1',
            'reason' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'El ID del empleado es obligatorio.',
            'employee_id.exists' => 'El empleado no existe.',
            'holiday_ids.required' => 'Debes proporcionar al menos un día festivo.',
            'holiday_ids.array' => 'El formato de los días festivos debe ser una lista.',
            'holiday_ids.*.exists' => 'Uno o más días festivos no existen.',
            'type.required' => 'El tipo de trabajo es obligatorio.',
            'type.in' => 'El tipo de trabajo debe ser "completo" o "horas".',
            'worked_value.required' => 'El valor trabajado es obligatorio.',
            'worked_value.numeric' => 'El valor trabajado debe ser numérico.',
            'reason.required' => 'El motivo es obligatorio.',
        ];
    }
}
