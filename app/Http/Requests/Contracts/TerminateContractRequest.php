<?php

namespace App\Http\Requests\Contracts;

use App\Models\Contracts\Contract;
use Illuminate\Foundation\Http\FormRequest;

class TerminateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Debe proporcionar una razón para finalizar el contrato.',
            'reason.string' => 'La razón debe ser una cadena de texto válida.',
            'reason.max' => 'La razón no debe exceder los 255 caracteres.',
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
                $validator->errors()->add('id', 'El contrato ya está finalizado.');
            }
        });
    }
}
