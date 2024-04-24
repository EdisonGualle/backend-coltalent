<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'nullable|string|max:50|unique:users,name,',
            'email' => 'nullable|email|unique:users,email,',
            'password' => 'nullable|string|min:8|confirmed',
            'employee_id' => 'nullable|exists:employees,id|unique:users,employee_id',
            'role_id' => 'nullable|exists:roles,id',
            'user_state_id' => 'nullable|exists:user_states,id',
        ];
    }
}
