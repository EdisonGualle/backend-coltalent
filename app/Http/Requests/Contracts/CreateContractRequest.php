<?php

namespace App\Http\Requests\Contracts;

use App\Models\Contracts\ContractType;
use App\Models\Employee\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', Rule::exists('employees', 'id')],
            'contract_type_id' => ['required', Rule::exists('contract_types', 'id')],
            'start_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'El empleado es obligatorio.',
            'employee_id.exists' => 'El empleado seleccionado no existe en el sistema.',
            'contract_type_id.required' => 'El tipo de contrato es obligatorio.',
            'contract_type_id.exists' => 'El tipo de contrato seleccionado no está disponible.',
            'start_date.required' => 'La fecha de inicio del contrato es obligatoria.',
            'start_date.date' => 'La fecha de inicio debe tener un formato válido (YYYY-MM-DD).',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $employee = Employee::find($this->employee_id);
            $contractType = ContractType::find($this->contract_type_id);

            if (!$contractType || $contractType->trashed()) {
                $validator->errors()->add('contract_type_id', 'El tipo de contrato seleccionado no está disponible.');
            }

            if ($employee && $employee->contracts()->where('is_active', true)->exists()) {
                $validator->errors()->add('employee_id', 'El empleado ya tiene un contrato activo.');
            }

            $overlappingContract = $employee?->contracts()
                ->where('end_date', '>=', $this->start_date)
                ->where('is_active', false)
                ->first();

            if ($overlappingContract) {
                $validator->errors()->add('start_date', 'La fecha de inicio no puede solaparse con contratos previos.');
            }
        });
    }
}
