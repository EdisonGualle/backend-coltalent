<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules()
    {
        return [
            'user' => 'string',
            'email' => 'string|email',
            'password' => 'required|string',
        ];
    }
}