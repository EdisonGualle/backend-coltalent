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
            'publisher' => 'string|max:255',
            'isbn_issn' => 'string|max:255|unique:publications,isbn_issn',
            'authorship' => 'boolean',
        ];
    }
}
