<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class StoreRejectionReasonRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:100|unique:rejection_reasons,reason',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'El motivo de rechazo es obligatorio.',
            'reason.string' => 'El motivo de rechazo debe ser una cadena de texto.',
            'reason.max' => 'El motivo de rechazo no puede exceder los 100 caracteres.',
            'reason.unique' => 'El motivo de rechazo ya existe.',
        ];
    }
}
