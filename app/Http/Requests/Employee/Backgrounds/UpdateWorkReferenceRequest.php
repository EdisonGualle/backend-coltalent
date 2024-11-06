<?php

namespace App\Http\Requests\Employee\Backgrounds;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkReferenceRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => [
                'string',
                'regex:/^[a-zA-Z\s]+$/',
                'max:150',
                // Verifica que sea único el nombre de la referencia de un empleado.
                Rule::unique('employee_work_references')->where(function ($query) {
                    return $query->where('employee_id', $this->route('employee'));
                })->ignore($this->route('work_reference')),
            ],
            'position' => 'string|max:150',
            'company_name' => 'string|max:150',
            'contact_number' => 'numeric|digits:10',
            'relationship_type' => 'string|regex:/^[a-zA-Z\s]+$/|max:100',
        ];
    }

    public function messages()
    {
        return [
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.regex' => 'El nombre no es valido.',
            'name.max' => 'El nombre no debe exceder los 150 caracteres.',
            'name.unique' => 'Ya existe una referencia de trabajo con este nombre.',
            'position.string' => 'El puesto debe ser una cadena de texto.',
            'position.max' => 'El puesto no debe exceder los 150 caracteres.',
            'company_name.string' => 'El nombre de la empresa debe ser una cadena de texto.',
            'company_name.max' => 'El nombre de la empresa no debe exceder los 150 caracteres.',
            'contact_number.numeric' => 'El número de contacto debe ser un número.',
            'contact_number.digits' => 'El número de contacto debe tener 10 dígitos.',
            'relationship_type.string' => 'El tipo de relación debe ser una cadena de texto.',
            'relationship_type.regex' => 'El tipo de relación no es valido.',
            'relationship_type.max' => 'El tipo de relación no debe exceder los 100 caracteres.',
        ];
    }  
}
