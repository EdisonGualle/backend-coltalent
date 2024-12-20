<?php

namespace App\Http\Requests\Contracts;

use App\Models\Contracts\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class RenewContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [
            'id.exists' => 'El contrato especificado no existe.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $contract = Contract::find($this->route('id'));

            if (!$contract) {
                $validator->errors()->add('id', 'El contrato especificado no existe.');
                return;
            }

            if (!$contract->is_active) {
                $validator->errors()->add('id', 'Solo se pueden renovar contratos que estén activos.');
            }

            if (!$contract->contractType->renewable) {
                $validator->errors()->add('id', 'Este tipo de contrato no permite renovaciones.');
            }

            if (is_null($contract->end_date)) {
                $validator->errors()->add('id', 'No se puede renovar un contrato que no tiene fecha de finalización.');
            }
        });
    }
}
