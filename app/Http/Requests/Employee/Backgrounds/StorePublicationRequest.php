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
            'publisher' => 'required|string|max:150',
            'isbn_issn' => [
                'required',
                'string',
                'max:150',
                // Verifica que sea único el isbn_issn para el empleado actual
                Rule::unique('employee_publications')->where(function ($query) {
                    return $query->where('employee_id', $this->route('employee'));
                }),
            ],
            'authorship' => 'required|in:SI,NO,si,no,Si, No',
        ];
    }

    public function messages()
    {
        return [
            'publication_type_id.exists' => 'El tipo de publicación seleccionado no es válido.',
            'title.string' => 'El título debe ser una cadena de texto.',
            'title.max' => 'El título no debe exceder los 255 caracteres.',
            'publisher.string' => 'El nombre del editor debe ser una cadena de texto.',
            'publisher.max' => 'El nombre del editor no debe exceder los 150 caracteres.',
            'isbn_issn.string' => 'El ISBN/ISSN debe ser una cadena de texto.',
            'isbn_issn.max' => 'El ISBN/ISSN no debe exceder los 150 caracteres.',
            'isbn_issn.unique' => 'El ISBN/ISSN ya está registrado.',
            'authorship.in' => 'El campo de autoría no es válido.',
        ];
    }
}
