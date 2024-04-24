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
                'max:255',
                //Verifica que sea unico el nombre de la referencia de un empleado.
                Rule::unique('work_references')->where(function ($query) {
                    return $query->where('employee_id', $this->route('employee'));
                }),
            ],
            'position' => 'string|max:255',
            'company_name' => 'string|max:255',
            'contact_number' => 'numeric|digits:10',
            'relationship_type' => 'string|max:255',
        ];
    }
    
}
