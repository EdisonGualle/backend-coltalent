<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:50|unique:users,name',
            'email' => 'required|email|unique:users,email',
            'employee_id' => 'required|exists:employees,id|unique:users,employee_id',
            'role_id' => 'nullable|exists:roles,id',
            'user_state_id' => 'nullable|exists:user_states,id',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.unique' => 'Ya existe un usuario con ese nombre.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser una dirección de correo válida.',
            'email.unique' => 'Ya existe un usuario con ese correo electrónico.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'employee_id.required' => 'El identificador de empleado es obligatorio.',
            'employee_id.unique' => 'Ya existe un usuario asociado a ese empleado.',
            'user_state_id.exists' => 'El estado de usuario seleccionado no existe.',
        ];
    }
}