<?php

namespace App\Http\Requests\Employee\Backgrounds;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreWorkReferenceRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'regex:/^[a-zA-Z\s]+$/',
                'max:255',
                Rule::unique('employee_work_references')->where(function ($query) {
                    return $query->where('employee_id', $this->route('employee'));
                }),
            ],
            'position' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'contact_number' => 'required|numeric|digits:10',
            'relationship_type' => 'required|string|regex:/^[a-zA-Z\s]+$/|max:100',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.regex' => 'El nombre no es valido.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',
            'name.unique' => 'Ya existe una referencia de trabajo con este nombre.',
            'position.required' => 'El puesto es obligatorio.',
            'position.string' => 'El puesto debe ser una cadena de texto.',
            'position.max' => 'El puesto no debe exceder los 255 caracteres.',
            'company_name.required' => 'El nombre de la empresa es obligatorio.',
            'company_name.string' => 'El nombre de la empresa debe ser una cadena de texto.',
            'company_name.max' => 'El nombre de la empresa no debe exceder los 255 caracteres.',
            'contact_number.required' => 'El número de contacto es obligatorio.',
            'contact_number.numeric' => 'El número de contacto debe ser un número.',
            'contact_number.digits' => 'El número de contacto debe tener 10 dígitos.',
            'relationship_type.required' => 'El tipo de relación es obligatorio.',
            'relationship_type.string' => 'El tipo de relación debe ser una cadena de texto.',
            'relationship_type.regex' => 'El tipo de relación no es valido.',
            'relationship_type.max' => 'Longitud maxima de 100 caracteres.',
        ];
    }


}


