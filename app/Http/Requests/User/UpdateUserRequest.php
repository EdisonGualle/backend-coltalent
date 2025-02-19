<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use App\Models\User;

class UpdateUserRequest extends FormRequest
{
    public function rules()
    {
        $userId = $this->route('user');
        $authUserId = auth()->id();

        $user = User::find($userId);

        $rules = [
            'name' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('users')->ignore($userId),
            ],
            'email' => [
                'nullable',
                'email',
                Rule::unique('users')->ignore($userId),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'employee_id' => ['nullable', 'exists:employees,id', 'unique:users,employee_id,' . $userId, function ($attribute, $value, $fail) use ($userId, $authUserId, $user) {
                if ($userId == $authUserId && $value != $user->employee_id) {
                    $fail('No puedes cambiar tu asociación con el empleado.');
                }
            }],
            'role_id' => ['nullable', 'exists:user_roles,id', function ($attribute, $value, $fail) use ($userId, $authUserId, $user) {
                if ($userId == $authUserId && $value != $user->role_id) {
                    $fail('No puedes cambiar tu propio rol.');
                }
            }],
            'user_state_id' => ['nullable', 'exists:user_states,id', function ($attribute, $value, $fail) use ($userId, $authUserId, $user) {
                if ($userId == $authUserId && $value != $user->user_state_id) {
                    $fail('No puedes cambiar tu propio estado.');
                }
            }],
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede tener más de 30 caracteres.',
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