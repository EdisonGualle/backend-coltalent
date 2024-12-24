<?php

namespace App\Http\Requests\Schedules;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDailyOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adjusted_start_time' => ['required', 'date_format:H:i:s', 'before:adjusted_end_time'],
            'adjusted_end_time' => ['required', 'date_format:H:i:s', 'after:adjusted_start_time'],
            'reason' => ['required', 'string', 'min:10'],
        ];
    }
}
