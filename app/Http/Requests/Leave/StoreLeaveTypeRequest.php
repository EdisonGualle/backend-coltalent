<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:leave_types,name',
            'description' => 'required|string|max:500',
            'max_duration' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^(0?[0-9]|1[0-9]|2[0-3]|24):([0-5][0-9])$/', $value)) {
                        $fail('El formato de la duración máxima es incorrecto.');
                        return;
                    }
                    if ($this->input('time_unit') === 'Días') {
                        if (!preg_match('/^\d+$/', $value)) {
                            $fail('El formato de la duración máxima es incorrecto.');
                        } elseif ((int)$value > 30) {
                            $fail('La duración máxima en días no puede exceder los 30 días.');
                        }
                    }
                },
            ],
            'requires_document' => 'nullable|in:Si,No',
            'advance_notice_days' => 'required|integer|min:1|max:10',
            'time_unit' => 'nullable|in:Días,Horas',
        ];
    }

    public function withValidator($validator)
    {
        $validator->sometimes('max_duration', 'required', function ($input) {
            return $input->time_unit !== null;
        });

        $validator->sometimes('time_unit', 'required', function ($input) {
            return $input->max_duration !== null;
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede exceder los 100 caracteres.',
            'name.unique' => 'Ya existe un tipo de permiso con ese nombre.',
            'description.required' => 'La descripción es obligatoria.',
            'description.string' => 'La descripción debe ser una cadena de texto.',
            'description.max' => 'La descripción no puede exceder los 500 caracteres.',
            'max_duration.required' => 'La duración máxima es obligatoria cuando la unidad de tiempo está presente.',
            'max_duration.string' => 'El campo max duration debe ser una cadena de caracteres.',
            'requires_document.in' => 'El campo de requerir documento debe ser "Si" o "No".',
            'advance_notice_days.required' => 'El aviso previo es obligatorio.',
            'advance_notice_days.integer' => 'El aviso previo debe ser un número entero.',
            'advance_notice_days.min' => 'El aviso previo debe ser al menos 1.',
            'advance_notice_days.max' => 'El aviso previo no puede exceder los 10 días.',
            'time_unit.required' => 'La unidad de tiempo es obligatoria cuando la duración máxima está presente.',
            'time_unit.in' => 'La unidad de tiempo debe ser "Días" o "Horas".',
        ];
    }
}
