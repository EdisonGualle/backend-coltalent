<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRejectionReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('rejection_reasons', 'reason')->ignore($this->route('rejection_reason')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'El motivo de rechazo es obligatorio.',
            'reason.string' => 'El motivo de rechazo debe ser una cadena de texto.',
            'reason.max' => 'El motivo de rechazo no puede exceder los 255 caracteres.',
            'reason.unique' => 'El motivo de rechazo ya existe.',
        ];
    }
}
