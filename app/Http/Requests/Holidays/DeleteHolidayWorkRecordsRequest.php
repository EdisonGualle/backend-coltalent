<?php

namespace App\Http\Requests\Holidays;

use Illuminate\Foundation\Http\FormRequest;

class DeleteHolidayWorkRecordsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'record_ids' => 'required|array|min:1',
            'record_ids.*' => 'exists:holiday_work_records,id',
        ];
    }

    public function messages(): array
    {
        return [
            'record_ids.required' => 'Debes proporcionar al menos un registro de trabajo.',
            'record_ids.array' => 'El formato de los registros debe ser una lista.',
            'record_ids.*.exists' => 'Uno o más registros no existen.',
        ];
    }
}
