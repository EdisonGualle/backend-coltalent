<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function rules()
    {
        return [
            'employee.identification' => 'digits:10|unique:employees,identification,',
            'employee.passport' => ' digits:12|unique:employees,passport,',
            'employee.first_name' => 'string',
            'employee.second_name' => 'string',
            'employee.last_name' => 'string',
            'employee.second_last_name' => 'string',
            'employee.date_of_birth' => 'date',
            'employee.gender' => 'in:Hombre,Mujer,Other',
            'employee.ethnicity' => 'string',
            'employee.marital_status' => 'string|in:Soltero,Casado,Viudo,Divorciado,Separado,Otro',
            'employee.blood_type' => 'string|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'employee.nationality' => 'string',
            'employee.military_id' => 'string',
            'employee.position_id' => 'numeric|exists:positions,id',
            'employee.contact.personal_email' => 'email|unique:contacts,personal_email,',
            'employee.contact.personal_phone' => 'digits:10',
            'employee.contact.home_phone' => 'digits:10',
            'employee.contact.work_phone' => 'digits:10',
            'employee.address.sector' => 'string|max:255',
            'employee.address.streets' => 'string|max:255',
            'employee.address.main_street' => 'string|max:255',
            'employee.address.secondary_street' => 'string|max:255',
            'employee.address.number' => 'numeric|max:5',
            'employee.address.reference' => 'string|max:255',
            'employee.address.parish_id' => 'exists:parishes,id',
        ];
    }
}