<?php

namespace App\Http\Requests\Schedules;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:100|unique:schedules,name,' . $this->route('id'),
            'description' => 'sometimes|required|string|max:255',
            'start_time' => 'sometimes|required|date_format:H:i:s',
            'end_time' => 'sometimes|required|date_format:H:i:s',
            'break_start_time' => 'nullable|date_format:H:i:s',
            'break_end_time' => 'nullable|date_format:H:i:s|after:break_start_time',
            'rest_days' => 'sometimes|required|array|min:1', // Días de descanso requeridos si se incluyen
            'rest_days.*' => 'integer|min:0|max:6', // Días válidos: 0 (domingo) a 6 (sábado)
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del horario es obligatorio.',
            'name.unique' => 'Ya existe un horario con este nombre.',
            'name.max' => 'El nombre no puede exceder los 100 caracteres.',
            'description.required' => 'La descripción del horario es obligatoria.',
            'description.max' => 'La descripción no puede exceder los 255 caracteres.',
            'start_time.required' => 'La hora de inicio es obligatoria.',
            'end_time.required' => 'La hora de finalización es obligatoria.',
            'end_time.after' => 'La hora de finalización debe ser después de la hora de inicio.',
            'break_end_time.after' => 'La hora de fin de la pausa debe ser después del inicio de la pausa.',
            'rest_days.required' => 'Debe especificar al menos un día de descanso.',
            'rest_days.array' => 'Los días de descanso deben ser una lista válida.',
            'rest_days.min' => 'Debe incluir al menos un día de descanso.',
            'rest_days.*.integer' => 'Cada día de descanso debe ser un número entre 0 (domingo) y 6 (sábado).',
            'rest_days.*.min' => 'Los días de descanso no pueden ser menores a 0.',
            'rest_days.*.max' => 'Los días de descanso no pueden ser mayores a 6.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $minHours = (int) DB::table('configurations')->where('key', 'daily_min_hours')->value('value');
            $maxHours = (int) DB::table('configurations')->where('key', 'daily_max_hours')->value('value');

            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');
            $breakStartTime = $this->input('break_start_time');
            $breakEndTime = $this->input('break_end_time');

            // Validar si start_time y end_time están presentes antes de procesar
            if ($startTime && $endTime) {
                $startTimeParsed = Carbon::createFromFormat('H:i:s', $startTime);
                $endTimeParsed = Carbon::createFromFormat('H:i:s', $endTime);

                // Manejar cruce de días
                if ($endTimeParsed < $startTimeParsed) {
                    $endTimeParsed->addDay();
                }

                $totalDuration = $endTimeParsed->diffInMinutes($startTimeParsed);

                // Restar duración de la pausa si existe
                $breakDuration = 0;
                if ($breakStartTime && $breakEndTime) {
                    $breakStartParsed = Carbon::createFromFormat('H:i:s', $breakStartTime);
                    $breakEndParsed = Carbon::createFromFormat('H:i:s', $breakEndTime);
                    $breakDuration = $breakEndParsed->diffInMinutes($breakStartParsed);
                }

                $effectiveDuration = ($totalDuration - $breakDuration) / 60;

                // Validar duración mínima y máxima
                if ($effectiveDuration < $minHours) {
                    $validator->errors()->add(
                        'end_time',
                        "La duración efectiva del horario no puede ser menor a {$minHours} horas."
                    );
                }

                if ($effectiveDuration > $maxHours) {
                    $validator->errors()->add(
                        'end_time',
                        "La duración efectiva del horario no puede ser mayor a {$maxHours} horas."
                    );
                }
            }

            // Validación dependiente para pausas
            if ($breakEndTime && !$breakStartTime) {
                $validator->errors()->add(
                    'break_start_time',
                    'Debe proporcionar la hora de inicio de la pausa si se proporciona la hora de fin.'
                );
            }

            if ($breakStartTime && !$breakEndTime) {
                $validator->errors()->add(
                    'break_end_time',
                    'Debe proporcionar la hora de fin de la pausa si se proporciona la hora de inicio.'
                );
            }
        });
    }
}
