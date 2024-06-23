<?php
namespace App\Http\Requests\Employee\Backgrounds;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLanguageRequest extends FormRequest
{
    public function rules()
    {
        return [
            'language' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('employee_languages')->where(function ($query) {
                    return $query->where('employee_id', $this->route('employee'));
                })->ignore($this->route('language')),
            ],
            'spoken_level' => 'nullable|integer|between:1,100',
            'written_level' => 'nullable|integer|between:1,100',
            'proficiency_certificate' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('employee_languages')->where(function ($query) {
                    return $query->where('employee_id', $this->route('employee'));
                })->ignore($this->route('language')),
            ],
            'issuing_institution' => 'nullable|string|max:255',
        ];
    }

    public function authorize()
    {
        return true;
    }

    public function messages()
    {
        return [
            'language.string' => 'El idioma debe ser una cadena de texto.',
            'language.max' => 'El idioma no debe exceder los 255 caracteres.',
            'language.unique' => 'El idioma ya está registrado.',
            'spoken_level.integer' => 'El nivel hablado debe ser un número entero.',
            'spoken_level.between' => 'El nivel hablado debe estar entre 1 y 100.',
            'written_level.integer' => 'El nivel escrito debe ser un número entero.',
            'written_level.between' => 'El nivel escrito debe estar entre 1 y 100.',
            'proficiency_certificate.string' => 'El certificado de competencia debe ser una cadena de texto.',
            'proficiency_certificate.max' => 'El certificado de competencia no debe exceder los 255 caracteres.',
            'proficiency_certificate.unique' => 'El certificado de competencia ya está registrado.',
            'issuing_institution.string' => 'La institución emisora debe ser una cadena de texto.',
            'issuing_institution.max' => 'La institución emisora no debe exceder los 255 caracteres.',
        ];
    }
}