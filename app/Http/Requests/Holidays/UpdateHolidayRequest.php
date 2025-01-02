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
                function ($attribute, $value, $fail) {
                    $isRecurringExists = Holiday::where('is_recurring', true)
                        ->whereRaw("DATE_FORMAT(date, '%m-%d') = DATE_FORMAT(?, '%m-%d')", [$value])
                        ->where('id', '!=', $this->route('id')) // Ignorar el registro actual
                        ->whereNull('deleted_at')
                        ->exists();

                    if ($isRecurringExists) {
                        $fail("Ya existe un día festivo recurrente para la fecha seleccionada.");
                    }


                    // Validar si ya existe la misma fecha exacta entre los días festivos activos
                    $exactDateExists = Holiday::where('date', $value)
                        ->where('id', '!=', $this->route('id')) // Ignorar el registro actual
                        ->whereNull('deleted_at') // Solo entre los activos
                        ->exists();

                    if ($exactDateExists) {
                        $fail("Ya existe un día festivo activo con la fecha exacta seleccionada.");
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
