<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Organization\Position;

class UpdatePositionRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'string|max:150|unique:positions,name,' . $this->route('position'),
            'function' => 'string|max:255',
            'direction_id' => 'nullable|exists:directions,id',
            'unit_id' => 'nullable|exists:units,id',
            'is_manager' => [
                'boolean',
                function ($attribute, $value, $fail) {
                    if ($value && $this->input('unit_id')) {
                        $exists = Position::where('unit_id', $this->input('unit_id'))->where('is_manager', 1)->where('id', '!=', $this->route('position'))->exists();
                        if ($exists) {
                            $fail('Ya existe un jefe en esta unidad.');
                        }
                    } elseif ($value && $this->input('direction_id')) {
                        $exists = Position::where('direction_id', $this->input('direction_id'))->where('is_manager', 1)->where('id', '!=', $this->route('position'))->exists();
                        if ($exists) {
                            $fail('Ya existe un jefe en esta dirección.');
                        }
                    }
                },
            ],
            'is_general_manager' => [
                'boolean',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $exists = Position::where('is_general_manager', 1)->where('id', '!=', $this->route('position'))->exists();
                        if ($exists) {
                            $fail('Ya existe un jefe general.');
                        }
                    }
                },
            ],
            'responsibilities' => 'nullable|array',
            'responsibilities.*' => 'string|max:255', 
        ];
    }

    public function messages()
    {
        return [
            'name.string' => 'El nombre del cargo debe ser una cadena de texto.',
            'name.max' => 'El nombre del cargo no puede tener más de 150 caracteres.',
            'name.unique' => 'Ya existe un cargo con ese nombre.',
            'function.string' => 'La función del cargo debe ser una cadena de texto.',
            'function.max' => 'La función del cargo no puede tener más de 255 caracteres.',
            'direction_id.exists' => 'La dirección seleccionada no existe.',
            'unit_id.exists' => 'La unidad seleccionada no existe.',
            'is_manager.boolean' => 'El campo is_manager debe ser verdadero o falso.',
            'is_general_manager.boolean' => 'El campo is_general_manager debe ser verdadero o falso.',
        ];
    }
}
