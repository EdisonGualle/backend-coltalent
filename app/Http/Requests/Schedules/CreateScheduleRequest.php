<?php

namespace App\Http\Requests\Schedules;

use App\Utilities\TimeFormatter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CreateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:schedules,name',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_start_time' => 'nullable|date_format:H:i',
            'break_end_time' => 'nullable|date_format:H:i|after:break_start_time',
            'rest_days' => 'required|array|min:1', 
            'rest_days.*' => 'integer|min:0|max:6', // Validar días válidos (0=domingo, 6=sábado)
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del horario es obligatorio.',
            'name.unique' => 'Ya existe un horario con este nombre.',
            'name.max' => 'El nombre del horario no puede exceder los 100 caracteres.',
            'start_time.required' => 'La hora de inicio es obligatoria.',
            'end_time.required' => 'La hora de finalización es obligatoria.',
            'end_time.after' => 'La hora de finalización debe ser después de la hora de inicio.',
            'break_end_time.after' => 'La hora de fin de la pausa debe ser después de la hora de inicio de la pausa.',
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
            // Verificar si start_time y end_time están presentes antes de validar
            if (!$this->has('start_time') || !$this->has('end_time')) {
                return;
            }
    
            // Obtener configuraciones generales de tiempos mínimos y máximos
            $minDailyWork = (int) DB::table('configurations')->where('key', 'min_daily_work')->value('value');
            $maxDailyWork = (int) DB::table('configurations')->where('key', 'max_daily_work')->value('value');
            $minDailyBreak = (int) DB::table('configurations')->where('key', 'min_daily_break')->value('value');
            $maxDailyBreak = (int) DB::table('configurations')->where('key', 'max_daily_break')->value('value');
    
            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');
            $breakStartTime = $this->input('break_start_time');
            $breakEndTime = $this->input('break_end_time');
    
            // Validar y ajustar si el horario cruza al siguiente día
            $startTimeParsed = Carbon::createFromFormat('H:i', $startTime);
            $endTimeParsed = Carbon::createFromFormat('H:i', $endTime);
            if ($endTimeParsed < $startTimeParsed) {
                $endTimeParsed->addDay();
            }
    
            $totalDuration = $endTimeParsed->diffInMinutes($startTimeParsed);
    
            // Restar duración de la pausa si existe
            $breakDuration = 0;
            if ($breakStartTime && $breakEndTime) {
                $breakStartParsed = Carbon::createFromFormat('H:i', $breakStartTime);
                $breakEndParsed = Carbon::createFromFormat('H:i', $breakEndTime);
                $breakDuration = $breakEndParsed->diffInMinutes($breakStartParsed);
    
                // Validar duración del descanso
                if ($breakDuration < $minDailyBreak) {
                    $validator->errors()->add(
                        'break_end_time',
                        "La duración del descanso no puede ser menor a " . TimeFormatter::formatMinutesToReadable($minDailyBreak) . "."
                    );
                }
    
                if ($breakDuration > $maxDailyBreak) {
                    $validator->errors()->add(
                        'break_end_time',
                        "La duración del descanso no puede ser mayor a " . TimeFormatter::formatMinutesToReadable($maxDailyBreak) . "."
                    );
                }
            }
    
            $effectiveDuration = $totalDuration - $breakDuration;
    
            // Validar tiempo mínimo y máximo de trabajo
            if ($effectiveDuration < $minDailyWork) {
                $validator->errors()->add(
                    'end_time',
                    "La duración efectiva del horario no puede ser menor a " . TimeFormatter::formatMinutesToReadable($minDailyWork) . "."
                );
            }
    
            if ($effectiveDuration > $maxDailyWork) {
                $validator->errors()->add(
                    'end_time',
                    "La duración efectiva del horario no puede ser mayor a " . TimeFormatter::formatMinutesToReadable($maxDailyWork) . "."
                );
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
