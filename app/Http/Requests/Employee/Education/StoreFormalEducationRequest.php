<?php

namespace App\Http\Requests\Employee\Education;

use App\Models\Other\State;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFormalEducationRequest extends FormRequest
{
    public function rules()
    {
        return [
            'level' => 'required|string|max:255',
            'institution' => 'required|string|max:255',
            'place' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'specialization' => 'required|string|max:255',
            'level_number' => 'required|integer|min:1|max:4',
            'status' => [
                'required',
                Rule::in(State::where('entity_type', 'FormalEducation')->pluck('state')->toArray())
            ],
            'date' => 'required|date',
            'registration' => 'required|string|max:255',
        ];
    }
}
