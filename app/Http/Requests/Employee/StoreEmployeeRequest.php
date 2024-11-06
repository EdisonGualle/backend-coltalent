<?php

namespace App\Http\Requests\Employee;

use App\Rules\UniquePositionForActiveEmployees;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreEmployeeRequest extends FormRequest
{
    public function rules()
    {
        return [
            // Employee - tabla
            'employee.identification' => 'required|digits:10|unique:employees,identification',
            'employee.passport' => 'nullable|digits:12|unique:employees,passport',
            'employee.first_name' => 'required|string|max:30',
            'employee.second_name' => 'nullable|string|max:30',
            'employee.last_name' => 'required|string|max:30',
            'employee.second_last_name' => 'nullable|string|max:255',
            'employee.date_of_birth' => 'required|date',
            'employee.gender' => 'required',
            'employee.ethnicity' => 'nullable|string|max:50',
            'employee.marital_status' => 'nullable|string|max:10',
            'employee.blood_type' => 'nullable|string|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'employee.nationality' => 'nullable|string|max:30',
            'employee.military_id' => 'nullable|string',
            // Contact - tabla
            'employee.contact.personal_email' => 'required|email|unique:employee_contacts,personal_email|unique:users,email',
            'employee.contact.personal_phone' => 'nullable|digits:10',
            'employee.contact.home_phone' => 'nullable|digits:10',
            'employee.contact.work_phone' => 'nullable|digits:10',
            // Address - tabla
            'employee.address.sector' => 'nullable|string|max:100',
            'employee.address.main_street' => 'nullable|string|max:100',
            'employee.address.secondary_street' => 'nullable|string|max:100',
            'employee.address.number' => 'nullable|numeric',
            'employee.address.reference' => 'nullable|string|max:255',
            'employee.address.parish_id' => 'nullable|exists:parishes,id',
            // Position - tabla
            'employee.position_id' => ['required', 'numeric', 'exists:positions,id', new UniquePositionForActiveEmployees],

             // Role - tabla
             'user.role_id' => 'required|exists:user_roles,id',
        ];
    }



    public function messages()
    {
        return [
            // Employee - tabla
            'employee.identification.required' => 'La cédula es obligatoria. Por favor, proporcione un número de cédula válido.',
            'employee.identification.digits' => 'La cédula debe tener exactamente 10 dígitos.',
            'employee.identification.unique' => 'Esta cédula ya está registrada en el sistema. Por favor, verifique el número.',
            
            'employee.passport.digits' => 'El pasaporte debe tener exactamente 12 dígitos.',
            'employee.passport.unique' => 'Este pasaporte ya está registrado en el sistema. Por favor, verifique el número.',
            
            'employee.first_name.required' => 'El primer nombre es obligatorio. Por favor, proporcione el nombre del empleado.',
            'employee.first_name.string' => 'El primer nombre debe ser una cadena de caracteres válida.',
            
            'employee.second_name.string' => 'El segundo nombre debe ser una cadena de caracteres válida.',
            
            'employee.last_name.required' => 'El primer apellido es obligatorio. Por favor, proporcione el apellido del empleado.',
            'employee.last_name.string' => 'El primer apellido debe ser una cadena de caracteres válida.',
            
            'employee.second_last_name.string' => 'El segundo apellido debe ser una cadena de caracteres válida.',
            
            'employee.date_of_birth.required' => 'La fecha de nacimiento es obligatoria. Por favor, proporcione una fecha de nacimiento válida.',
            'employee.date_of_birth.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            
            'employee.gender.required' => 'El género es obligatorio. Por favor, seleccione el género del empleado.',
            
            'employee.ethnicity.string' => 'La etnia debe ser una cadena de caracteres válida.',
            
            'employee.marital_status.string' => 'El estado civil debe ser una cadena de caracteres válida.',
            
            'employee.blood_type.in' => 'El tipo de sangre debe ser uno de los siguientes: A+, A-, B+, B-, AB+, AB-, O+, O-',
            
            'employee.nationality.string' => 'La nacionalidad debe ser una cadena de caracteres válida.',
            
            'employee.military_id.string' => 'La identificación militar debe ser una cadena de caracteres válida.',
            
            // Contact - tabla
            'employee.contact.personal_email.required' => 'El correo personal es obligatorio. Por favor, proporcione una dirección de correo electrónico válida.',
            'employee.contact.personal_email.email' => 'Por favor, proporcione una dirección de correo electrónico válida.',
            'employee.contact.personal_email.unique' => 'Este correo personal ya está registrado en el sistema. Por favor, verifique la dirección.',
            
            'employee.contact.personal_phone.digits' => 'El teléfono personal debe tener exactamente 10 dígitos.',
            
            'employee.contact.home_phone.digits' => 'El teléfono de casa debe tener exactamente 10 dígitos.',
            
            'employee.contact.work_phone.digits' => 'El teléfono de trabajo debe tener exactamente 10 dígitos.',
            
            // Address - tabla
            'employee.address.sector.string' => 'El sector debe ser una cadena de caracteres válida.',
            'employee.address.sector.max' => 'El sector no debe exceder los 255 caracteres.',
            
            'employee.address.main_street.string' => 'La calle principal debe ser una cadena de caracteres válida.',
            'employee.address.main_street.max' => 'La calle principal no debe exceder los 255 caracteres.',
            
            'employee.address.secondary_street.string' => 'La calle secundaria debe ser una cadena de caracteres válida.',
            'employee.address.secondary_street.max' => 'La calle secundaria no debe exceder los 255 caracteres.',
            
            'employee.address.number.numeric' => 'El número debe ser un valor numérico.',
            
            'employee.address.reference.string' => 'La referencia debe ser una cadena de caracteres válida.',
            'employee.address.reference.max' => 'La referencia no debe exceder los 255 caracteres.',
            
            'employee.address.parish_id.exists' => 'La parroquia seleccionada no existe en el sistema. Por favor, seleccione una parroquia válida.',
            
            // Position - tabla
            'employee.position_id.required' => 'El cargo es obligatorio. Por favor, seleccione un cargo válido para el empleado.',
            'employee.position_id.numeric' => 'El cargo debe ser un valor numérico.',
            'employee.position_id.exists' => 'El cargo seleccionado no existe en el sistema. Por favor, seleccione un cargo válido.',
            'employee.position_id.unique' => 'Este cargo ya está asignado a un empleado activo.',

            // Role - tabla
            'user.role_id.required' => 'El rol es obligatorio. Por favor, seleccione un rol válido para el usuario.',
            'user.role_id.exists' => 'El rol seleccionado no existe en el sistema. Por favor, seleccione un rol válido.',
        ];
    }
}
