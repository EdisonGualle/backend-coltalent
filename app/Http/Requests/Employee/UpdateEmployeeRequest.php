<?php

namespace App\Http\Requests\Employee;

use App\Models\Employee\Employee;
use App\Models\Employee\PersonalInfo\Contact;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\UniquePositionForActiveEmployees;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function rules()
    {
        $employeeId = $this->route('employee');
        $employee = Employee::find($employeeId);
        $employee = Employee::with('contact')->findOrFail($employeeId);


        return [
            'employee.identification' => 'nullable|digits:10|unique:employees,identification,' . $this->route('employee'),
            'employee.passport' => 'nullable|digits:12|unique:employees,passport,' . $this->route('employee'),
            'employee.first_name' => 'nullable|string',
            'employee.second_name' => 'nullable|string',
            'employee.last_name' => 'nullable|string',
            'employee.second_last_name' => 'nullable|string',
            'employee.date_of_birth' => 'nullable|date',
            'employee.gender' => 'nullable|string',
            'employee.ethnicity' => 'nullable|string',
            'employee.marital_status' => 'nullable|string',
            'employee.blood_type' => 'nullable|string',
            'employee.nationality' => 'nullable|string',
            'employee.military_id' => 'nullable|string',
            'employee.position_id' => [
                'nullable',
                'numeric',
                'exists:positions,id',
                new UniquePositionForActiveEmployees($employeeId)
            ],
            'employee.contact.personal_email' => [
                'nullable',
                'email',
                Rule::unique('employee_contacts', 'personal_email')->ignore($employee->contact_id)
            ],
            'employee.contact.personal_phone' => 'nullable|digits:10',
            'employee.contact.home_phone' => 'nullable|digits:10',
            'employee.contact.work_phone' => 'nullable|digits:10',
            'employee.address.sector' => 'nullable|string|max:255',
            'employee.address.main_street' => 'nullable|string|max:255',
            'employee.address.secondary_street' => 'nullable|string|max:255',
            'employee.address.number' => 'nullable|numeric|max:5',
            'employee.address.reference' => 'nullable|string|max:255',
            'employee.address.parish_id' => 'nullable|exists:parishes,id',
        ];
    }

    public function messages()
    {
        return [
            // Employee - tabla
            'employee.identification.digits' => 'La cédula debe tener exactamente 10 dígitos.',
            'employee.identification.unique' => 'Esta cédula ya está registrada en el sistema. Por favor, verifique el número.',

            'employee.passport.digits' => 'El pasaporte debe tener exactamente 12 dígitos.',
            'employee.passport.unique' => 'Este pasaporte ya está registrado en el sistema. Por favor, verifique el número.',

            'employee.first_name.string' => 'El primer nombre debe ser una cadena de caracteres válida.',

            'employee.second_name.string' => 'El segundo nombre debe ser una cadena de caracteres válida.',

            'employee.last_name.string' => 'El primer apellido debe ser una cadena de caracteres válida.',

            'employee.second_last_name.string' => 'El segundo apellido debe ser una cadena de caracteres válida.',

            'employee.date_of_birth.date' => 'La fecha de nacimiento debe ser una fecha válida.',

            'employee.gender.string' => 'El género debe ser una cadena de caracteres válida.',

            'employee.ethnicity.string' => 'La etnia debe ser una cadena de caracteres válida.',

            'employee.marital_status.string' => 'El estado civil debe ser una cadena de caracteres válida.',

            'employee.blood_type.string' => 'El tipo de sangre debe ser una cadena de caracteres válida.',

            'employee.nationality.string' => 'La nacionalidad debe ser una cadena de caracteres válida.',

            'employee.military_id.string' => 'La identificación militar debe ser una cadena de caracteres válida.',

            // Contact - tabla
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
            'employee.address.number.max' => 'El número no debe exceder los 5 dígitos.',

            'employee.address.reference.string' => 'La referencia debe ser una cadena de caracteres válida.',
            'employee.address.reference.max' => 'La referencia no debe exceder los 255 caracteres.',

            'employee.address.parish_id.exists' => 'La parroquia seleccionada no existe en el sistema. Por favor, seleccione una parroquia válida.',

            // Position - tabla
            'employee.position_id.numeric' => 'El cargo debe ser un valor numérico.',
            'employee.position_id.exists' => 'El cargo seleccionado no existe en el sistema. Por favor, seleccione un cargo válido.',
            'employee.position_id.unique' => 'Este cargo ya está asignado a un empleado activo.',
        ];
    }
}
