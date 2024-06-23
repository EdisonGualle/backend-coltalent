<?php

namespace App\Http\Requests\Employee\Backgrounds;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class StoreWorkExperienceRequest extends FormRequest
{
    public function rules()
    {
        Validator::extend('min_month_difference', function ($attribute, $value, $parameters, $validator) {
            $from = request('from');
            $to = request('to');

            if (!$from || !$to) {
                return false;
            }

            $fromDate = new \DateTime($from);
            $toDate = new \DateTime($to);
            $diff = $fromDate->diff($toDate);

            // Verificar si la diferencia es al menos de 1 mes
            return $diff->m >= 1 || $diff->y > 0;
        });

        return [
            'from' => 'required|date|after_or_equal:1990-01-01',
            'to' => 'required|date|after:from|before_or_equal:today|min_month_difference',
            'position' => 'required|string|max:255',
            'institution' => 'required|string|max:255',
            'responsibilities' => 'required|string',
            'activities' => 'nullable|string',
            'functions' => 'nullable|string',
            'departure_reason' => 'nullable|string',
            'note' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'from.required' => 'La fecha de inicio es obligatoria.',
            'from.date' => 'La fecha de inicio debe ser una fecha válida.',
            'from.after_or_equal' => 'La fecha de inicio debe ser igual o posterior a 1990-01-01.',
            'to.required' => 'La fecha de finalización es obligatoria.',
            'to.date' => 'La fecha de finalización debe ser una fecha válida.',
            'to.after' => 'La fecha de finalización debe ser posterior a la fecha de inicio.',
            'to.before_or_equal' => 'La fecha de finalización debe ser igual o anterior a hoy.',
            'to.min_month_difference' => 'Debe tener minimo un mes de experiencia.',
            'position.required' => 'El cargo es obligatorio.',
            'position.string' => 'El cargo debe ser una cadena de texto.',
            'position.max' => 'El cargo no debe exceder los 255 caracteres.',
            'institution.required' => 'La institución es obligatoria.',
            'institution.string' => 'La institución debe ser una cadena de texto.',
            'institution.max' => 'La institución no debe exceder los 255 caracteres.',
            'responsibilities.required' => 'Las responsabilidades son obligatorias.',
            'responsibilities.string' => 'Las responsabilidades deben ser una cadena de texto.',
            'activities.string' => 'Las actividades deben ser una cadena de texto.',
            'functions.string' => 'Las funciones deben ser una cadena de texto.',
            'departure_reason.string' => 'La razón de salida debe ser una cadena de texto.',
            'note.string' => 'La nota debe ser una cadena de texto.',
        ];
    }
}