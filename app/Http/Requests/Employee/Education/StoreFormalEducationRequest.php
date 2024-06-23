<?php

namespace App\Http\Requests\Employee\Education;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class StoreFormalEducationRequest extends FormRequest
{
    public function rules()
    {
        $minDate = Carbon::now()->subYears(100)->format('Y-m-d');

        return [
            'level_id' => 'required|exists:education_levels,id',
            'institution' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'state_id' => 'required|exists:education_states,id',
            'date' => ['nullable', 'date', 'before_or_equal:today', 'after_or_equal:' . $minDate],
            'registration' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'level_id.required' => 'El nivel de educación es obligatorio.',
            'level_id.exists' => 'El nivel de educación seleccionado no es válido.',
            'institution.required' => 'La institución es obligatoria.',
            'institution.string' => 'La institución debe ser una cadena de texto.',
            'institution.max' => 'La institución no puede tener más de 255 caracteres.',
            'title.required' => 'El título es obligatorio.',
            'title.string' => 'El título debe ser una cadena de texto.',
            'title.max' => 'El título no puede tener más de 255 caracteres.',
            'specialization.string' => 'La especialización debe ser una cadena de texto.',
            'specialization.max' => 'La especialización no puede tener más de 255 caracteres.',
            'state_id.required' => 'El estado de la educación es obligatorio.',
            'state_id.exists' => 'El estado de la educación seleccionado no es válido.',
            'date.required' => 'La fecha es obligatoria.',
            'date.date' => 'La fecha debe ser una fecha válida.',
            'date.before_or_equal' => 'La fecha no puede ser una fecha futura.',
            'date.after_or_equal' => 'La fecha no puede ser anterior a hace 100 años.',
            'registration.required' => 'El registro es obligatorio.',
            'registration.string' => 'El registro debe ser una cadena de texto.',
            'registration.max' => 'El registro no puede tener más de 255 caracteres.',
        ];
    }
}
