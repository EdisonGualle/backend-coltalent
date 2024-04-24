<?php

namespace App\Http\Requests\Employee\Backgrounds;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkExperienceRequest extends FormRequest
{
    public function rules()
    {
        return [
            'from' => 'required|date|after_or_equal:1990-01-01',
            'to' => 'required|date|after:from|before_or_equal:today',
            'position' => 'required|string|max:255',
            'institution' => 'required|string|max:255',
            'responsibilities' => 'nullable|string',
            'activities' => 'nullable|string',
            'functions' => 'nullable|string',
            'departure_reason' => 'nullable|string',
            'grade' => 'nullable|numeric|between:1,5',
        ];
    }
}
