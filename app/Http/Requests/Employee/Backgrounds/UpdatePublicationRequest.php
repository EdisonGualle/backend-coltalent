<?php

namespace App\Http\Requests\Employee\Backgrounds;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePublicationRequest extends FormRequest
{
    public function rules()
    {
        return [
            'publication_type_id' => 'exists:publication_types,id',
            'title' => 'string|max:255',
            'publisher' => 'string|max:150',
            'isbn_issn' => 'string|max:150|unique:employee_publications,isbn_issn,' . $this->route('publication'),
            'authorship' => 'in:SI,NO,si,no,Si, No',
        ];
    }


    public function messages()
    {
        return [
            'publication_type_id.exists' => 'El tipo de publicación seleccionado no es válido.',
            'title.string' => 'El título debe ser una cadena de texto.',
            'title.max' => 'El título no debe exceder los 150 caracteres.',
            'publisher.string' => 'El nombre del editor debe ser una cadena de texto.',
            'publisher.max' => 'El nombre del editor no debe exceder los 150 caracteres.',
            'isbn_issn.string' => 'El ISBN/ISSN debe ser una cadena de texto.',
            'isbn_issn.max' => 'El ISBN/ISSN no debe exceder los 150 caracteres.',
            'isbn_issn.unique' => 'El ISBN/ISSN ya está registrado.',
            'authorship.in' => 'El campo de autoría no es válido.',
        ];
    }
}
