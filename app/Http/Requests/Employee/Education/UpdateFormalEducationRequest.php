<?php

namespace App\Http\Requests\Employee\Education;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Other\State;

class UpdateFormalEducationRequest extends FormRequest
{
    public function rules()
    {
        return [
            'level' => 'string|max:255',
            'institution' => 'string|max:255',
            'place' => 'string|max:255',
            'title' => 'string|max:255',
            'specialization' => 'string|max:255',
            'level_number' => 'integer|min:1|max:4',
            'status' => [
                Rule::in(State::where('entity_type', 'FormalEducation')->pluck('state')->toArray())
            ],
            'date' => 'date',
            'registration' => 'string|max:255',
        ];
    }
}
