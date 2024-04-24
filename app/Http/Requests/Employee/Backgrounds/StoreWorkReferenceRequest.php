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
                Rule::unique('work_references')->where(function ($query) {
                    return $query->where('employee_id', $this->route('employee'));
                }),

            ],
            'position' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'contact_number' => 'required|numeric|digits:10',
            'relationship_type' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'name.unique' => 'Ya existe una referencia de trabajo con este nombre.'
        ];
    }


}


