<?php

namespace App\Http\Requests\Contracts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UpdateContractTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Obtener los valores desde la tabla de configuraciones
        $weeklyHoursMin = DB::table('configurations')->where('key', 'weekly_hours_min')->value('value');
        $weeklyHoursMax = DB::table('configurations')->where('key', 'weekly_hours_max')->value('value');

        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:100',
                Rule::unique('contract_types', 'name')->ignore($this->route('id')),
            ],
            'description' => 'nullable|string|max:255',
            'max_duration_months' => 'nullable|integer|min:1',
            'renewable' => 'sometimes|required|boolean',
            'vacation_days_per_year' => 'sometimes|required|integer|min:1',
            'max_vacation_days' => 'sometimes|required|integer|min:1|gte:vacation_days_per_year',
            'min_tenure_months_for_vacation' => 'sometimes|required|integer|min:0',
            'weekly_hours' => "sometimes|required|integer|min:$weeklyHoursMin|max:$weeklyHoursMax", 
        ];
    }

    public function messages(): array
    {
        $weeklyHoursMin = DB::table('configurations')->where('key', 'weekly_hours_min')->value('value');
        $weeklyHoursMax = DB::table('configurations')->where('key', 'weekly_hours_max')->value('value');

        return [
            'name.required' => 'El nombre del tipo de contrato es obligatorio.',
            'name.unique' => 'El nombre del tipo de contrato ya existe.',
            'name.max' => 'El nombre no puede exceder los 100 caracteres.',
            'description.max' => 'La descripción no puede exceder los 255 caracteres.',
            'max_duration_months.integer' => 'La duración máxima debe ser un número entero.',
            'max_duration_months.min' => 'La duración máxima debe ser al menos 1 mes.',
            'renewable.required' => 'Es necesario especificar si el contrato es renovable.',
            'renewable.boolean' => 'El campo renovable debe ser verdadero o falso.',
            'vacation_days_per_year.required' => 'Los días de vacaciones por año son obligatorios.',
            'vacation_days_per_year.integer' => 'Los días de vacaciones deben ser un número entero.',
            'vacation_days_per_year.min' => 'Debe haber al menos 1 día de vacaciones por año.',
            'max_vacation_days.required' => 'El máximo acumulable de días es obligatorio.',
            'max_vacation_days.integer' => 'El máximo acumulable de días debe ser un número entero.',
            'max_vacation_days.min' => 'El máximo acumulable debe ser al menos 1 día.',
            'max_vacation_days.gte' => 'El máximo acumulable de días no puede ser menor que los días de vacaciones por año.',
            'min_tenure_months_for_vacation.required' => 'El tiempo mínimo de antigüedad es obligatorio.',
            'min_tenure_months_for_vacation.integer' => 'El tiempo mínimo de antigüedad debe ser un número entero.',
            'min_tenure_months_for_vacation.min' => 'El tiempo mínimo de antigüedad no puede ser negativo.',
            'renewable.false' => 'Un contrato indefinido no puede ser renovable.',
            'weekly_hours.required' => 'Las horas semanales son obligatorias.',
            'weekly_hours.integer' => 'Las horas semanales deben ser un número entero.',
            'weekly_hours.min' => "Las horas semanales no pueden ser menores a $weeklyHoursMin.",
            'weekly_hours.max' => "Las horas semanales no pueden exceder $weeklyHoursMax.",
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $maxDurationMonths = $this->input('max_duration_months');
            $renewable = $this->input('renewable');

            // Validación: Contratos indefinidos no pueden ser renovables
            if (is_null($maxDurationMonths) && $renewable) {
                $validator->errors()->add(
                    'renewable',
                    'Un contrato indefinido no puede ser renovable.'
                );
            }
        });
    }
}
