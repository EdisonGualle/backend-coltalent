<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function rules()
    {
        $userId = $this->route('user');

        return [
            'name' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users')->ignore($userId),
            ],
            'email' => [
                'nullable',
                'email',
                Rule::unique('users')->ignore($userId),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'employee_id' => 'nullable|exists:employees,id|unique:users,employee_id,' . $userId,
            'role_id' => 'nullable|exists:roles,id',
            'user_state_id' => 'nullable|exists:user_states,id',
        ];
    }

    public function messages()
    {
        return [
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede tener más de 50 caracteres.',
            'email.email' => 'El correo electrónico debe ser una dirección de correo electrónico válida.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'employee_id.exists' => 'El empleado especificado no existe.',
            'role_id.exists' => 'El rol especificado no existe.',
            'user_state_id.exists' => 'El estado de usuario especificado no existe.',
            'name.unique' => 'El nombre ya está en uso por otro usuario.',
            'email.unique' => 'El correo electrónico ya está en uso por otro usuario.',
            'employee_id.unique' => 'Ya existe un usuario asociado a ese empleado.',

        ];
    }
}
