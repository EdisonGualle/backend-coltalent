<?php

namespace App\Http\Requests\Employee\Backgrounds;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicationRequest extends FormRequest
{
    public function rules()
    {
        return [
            'publication_type_id' => 'required|exists:publication_types,id',
            'title' => 'required|string|max:255',
            'publisher' => 'required|string|max:255',
            'isbn_issn' => [
                'required',
                'string',
                'max:255',
                // Verifica que sea único el isbn_issn para el empleado actual
                Rule::unique('publications')->where(function ($query) {
                    return $query->where('employee_id', $this->route('employee'));
                }),
            ],
            'authorship' => 'required|boolean',
        ];
    }
}
