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
                function ($attribute, $value, $fail) {
                    if ($this->input('time_unit') === 'Días') {
                        if (!preg_match('/^\d+$/', $value)) {
                            $fail('El formato de la duración máxima en días es incorrecto.');
                        } elseif ((int)$value < 1 || (int)$value > 30) {
                            $fail('La duración máxima en días debe estar entre 1 y 30 días.');
                        }
                    } elseif ($this->input('time_unit') === 'Horas') {
                        if (!preg_match('/^(0?[0-9]|1[0-9]|2[0-3]):([0-5][0-9])$/', $value)) {
                            $fail('El formato de la duración máxima en horas es incorrecto.');
                        } else {
                            // Convertir a minutos para validar el rango
                            list($hours, $minutes) = explode(':', $value);
                            $totalMinutes = $hours * 60 + $minutes;
                            $minMinutes = 30; // 00:30
                            $maxMinutes = 450; // 07:30
                            if ($totalMinutes < $minMinutes || $totalMinutes > $maxMinutes) {
                                $fail('La duración máxima en horas debe estar entre 00:30 y 07:30 horas.');
                            }
                        }
                    }
                },
            ],
            'requires_document' => 'required|in:Si,No',
            'advance_notice_days' => 'required|integer|min:1|max:10',
            'time_unit' => 'nullable|in:Días,Horas',
            'icon' => 'required|string|max:30'
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
            'max_duration.string' => 'El campo duración máxima debe ser una cadena de caracteres.',
            'requires_document.in' => 'El campo de requerir documento debe ser "Si" o "No".',
            'advance_notice_days.required' => 'El aviso previo es obligatorio.',
            'advance_notice_days.integer' => 'El aviso previo debe ser un número entero.',
            'advance_notice_days.min' => 'El aviso previo debe ser al menos 1.',
            'advance_notice_days.max' => 'El aviso previo no puede exceder los 10 días.',
            'time_unit.required' => 'La unidad de tiempo es obligatoria cuando la duración máxima está presente.',
            'time_unit.in' => 'La unidad de tiempo debe ser "Días" o "Horas".',
            'icon.required' => 'El icono es obligatorio.',
            'icon.string' => 'El icono debe ser una cadena de texto.',
            'icon.max' => 'El icono no puede exceder los 30 caracteres.'
        ];
    }
}
