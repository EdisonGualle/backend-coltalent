<?php

namespace App\Http\Requests\Holidays;

use App\Models\Holidays\Holiday;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
          'date' => [
                'required',
                'date',
                Rule::unique('holidays', 'date')->ignore($this->route('id')),
                function ($attribute, $value, $fail) {
                    $isRecurringExists = Holiday::where('is_recurring', true)
                        ->whereRaw("DATE_FORMAT(date, '%m-%d') = DATE_FORMAT(?, '%m-%d')", [$value])
                        ->where('id', '!=', $this->route('id')) // Ignorar el registro actual
                        ->whereNull('deleted_at')
                        ->exists();

                    if ($isRecurringExists) {
                        $fail("Ya existe un día festivo recurrente para la fecha {$value}.");
                    }
                },
            ],
            'name' => 'required|string|max:100',
            'is_recurring' => 'required|boolean',
            'applies_to_all' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'La fecha es obligatoria.',
            'date.date' => 'La fecha debe ser válida.',
            'date.unique' => 'Ya existe un día festivo con esta fecha.',
            'name.required' => 'El nombre del día festivo es obligatorio.',
            'name.string' => 'El nombre debe ser un texto válido.',
            'name.max' => 'El nombre no puede exceder los 100 caracteres.',
            'is_recurring.required' => 'Es necesario especificar si el día festivo es recurrente.',
            'is_recurring.boolean' => 'El valor debe ser verdadero o falso.',
            'applies_to_all.required' => 'Indica si el día festivo aplica a todos.',
            'applies_to_all.boolean' => 'El valor debe ser verdadero o falso.',
        ];
    }
}
