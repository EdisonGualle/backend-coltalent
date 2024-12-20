<?php

namespace App\Http\Requests\Schedules;

use App\Models\Employee\Employee;
use App\Models\Schedules\Schedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CreateEmployeeScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_id' => [
                'required',
                Rule::exists('schedules', 'id'),
            ],
            'start_date' => [
                'nullable', // Opcional, pero si se proporciona, debe ser una fecha válida
                'date',
            ],
            'end_date' => [
                'nullable', // Opcional, pero si se proporciona, debe ser una fecha válida
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'schedule_id.required' => 'El horario es obligatorio.',
            'schedule_id.exists' => 'El horario seleccionado no existe.',
            'start_date.date' => 'La fecha de inicio debe ser una fecha válida.',
            'start_date.before' => 'La fecha de inicio debe ser anterior a la fecha de fin.',
            'end_date.date' => 'La fecha de fin debe ser una fecha válida.',
            'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ];
    }


public function withValidator($validator)
{
    $validator->after(function ($validator) {
        $employeeId = $this->route('employee_id');
        $scheduleId = $this->input('schedule_id');
        $startDate = $this->input('start_date');
        $endDate = $this->input('end_date');

        // Validar que el empleado existe
        $employee = Employee::find($employeeId);
        if (!$employee) {
            $validator->errors()->add('employee_id', 'El empleado seleccionado no existe.');
            return;
        }

        // Validar que el empleado tiene un contrato activo
        $activeContract = $employee->contracts()->where('is_active', true)->first();
        if (!$activeContract) {
            $validator->errors()->add('employee_id', 'El empleado no tiene un contrato activo.');
            return;
        }

          // Si una fecha es proporcionada, la otra debe ser obligatoria
          if ($startDate && !$endDate) {
            $validator->errors()->add('end_date', 'Debe proporcionar una fecha de fin si proporciona una fecha de inicio.');
        }

        if ($endDate && !$startDate) {
            $validator->errors()->add('start_date', 'Debe proporcionar una fecha de inicio si proporciona una fecha de fin.');
        }

         // Validar relación entre fechas solo si ambas están presentes
         if ($startDate && $endDate) {
            if (Carbon::parse($startDate)->gte(Carbon::parse($endDate))) {
                $validator->errors()->add('start_date', 'La fecha de inicio debe ser anterior a la fecha de fin.');
            }
        }

        // Obtener el tipo de contrato y sus horas semanales
        $contractType = $activeContract->contractType;
        if (!$contractType || !$contractType->weekly_hours) {
            $validator->errors()->add(
                'employee_id',
                'El tipo de contrato del empleado no tiene configuradas las horas semanales requeridas.'
            );
            return;
        }

        // Validar que el horario cumple con las horas semanales requeridas
        $schedule = Schedule::find($scheduleId);
        if ($schedule) {
            $scheduleWeeklyHours = $this->calculateWeeklyHours($schedule);
            if ($scheduleWeeklyHours !== (int) $contractType->weekly_hours) {
                $validator->errors()->add(
                    'schedule_id',
                    "El horario seleccionado no cumple con las {$contractType->weekly_hours} horas semanales requeridas por el contrato."
                );
            }
        }
    });
}


    /**
     * Calcular las horas semanales efectivas de un horario.
     */
    private function calculateWeeklyHours(Schedule $schedule): int
    {
        $startTime = Carbon::createFromFormat('H:i:s', $schedule->start_time);
        $endTime = Carbon::createFromFormat('H:i:s', $schedule->end_time);

        if ($endTime->lt($startTime)) {
            $endTime->addDay();
        }

        $dailyHours = $endTime->diffInHours($startTime);

        // Restar la duración de las pausas si existen
        $breakStartTime = $schedule->break_start_time ? Carbon::createFromFormat('H:i:s', $schedule->break_start_time) : null;
        $breakEndTime = $schedule->break_end_time ? Carbon::createFromFormat('H:i:s', $schedule->break_end_time) : null;
        $breakDuration = 0;

        if ($breakStartTime && $breakEndTime) {
            $breakDuration = $breakEndTime->diffInHours($breakStartTime);
        }

        $effectiveDailyHours = $dailyHours - $breakDuration;

        LOG::info('effectiveDailyHours: ' . $effectiveDailyHours);

        // Días laborales en la semana (7 días - días de descanso)
        $workDays = 7 - count($schedule->rest_days ?? []);

        log::info('workDays: ' . $workDays);

        LOG::info('effectiveDailyHours * workDays: ' . $effectiveDailyHours * $workDays);

        return $effectiveDailyHours * $workDays;
    }
}
