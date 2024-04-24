<?php

namespace App\Http\Requests\Employee\Backgrounds;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkExperienceRequest extends FormRequest
{
    public function rules()
    {
        return [
            'from' => 'date|after_or_equal:1990-01-01',
            'to' => 'date|after:from|before_or_equal:today',
            'position' => 'string|max:255',
            'institution' => 'string|max:255',
            'responsibilities' => 'string',
            'activities' => 'string',
            'functions' => 'string',
            'departure_reason' => 'string',
            'grade' => 'numeric|between:1,5',
        ];
    }
}
