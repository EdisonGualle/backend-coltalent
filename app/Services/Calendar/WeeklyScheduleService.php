<?php

namespace App\Services\Calendar;

use App\Models\Schedules\EmployeeSchedule;
use Carbon\Carbon;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class WeeklyScheduleService extends ResponseService
{
    public function getWeeklySchedule(int $employeeId): JsonResponse
    {
        try {
            // Asegurar que la semana actual comience desde el lunes y termine el domingo
            $currentDate = Carbon::now();
            $startOfWeek = $currentDate->copy()->startOfWeek(1); // 1 es para Lunes
            $endOfWeek = $startOfWeek->copy()->addDays(6); // Siempre domingo

            $weeklySchedule = [];
            $currentDay = $startOfWeek->copy();

            while ($currentDay->lte($endOfWeek)) { // Asegura que se incluyan todos los días
                $dayInfo = [
                    'date' => $currentDay->toDateString(),
                    'day_name' => $this->getDayName($currentDay->dayOfWeek),
                    'type' => null,
                    'reason' => null,
                    'work_schedule' => null,
                ];

                // Verificar si tiene un horario activo
                $schedule = $this->getActiveSchedule($employeeId, $currentDay);

                if (!$schedule) {
                    // Día sin horario asignado
                    $dayInfo['type'] = 'no_schedule';
                    $dayInfo['reason'] = 'Sin horario asignado';
                } else {
                    $restDays = $schedule->schedule->rest_days;
                    if (in_array($currentDay->dayOfWeek, $restDays)) {
                        // Día de descanso
                        $dayInfo['type'] = 'rest_day';
                        $dayInfo['reason'] = 'Día de descanso';
                    } else {
                        // Día laboral
                        $dayInfo['type'] = 'work_day';
                        $dayInfo['reason'] = 'Día laboral';
                        $dayInfo['work_schedule'] = [
                            'start_time' => Carbon::parse($schedule->schedule->start_time)->format('H:i'),
                            'end_time' => Carbon::parse($schedule->schedule->end_time)->format('H:i'),
                            'break_start_time' => $schedule->schedule->break_start_time
                                ? Carbon::parse($schedule->schedule->break_start_time)->format('H:i')
                                : null,
                            'break_end_time' => $schedule->schedule->break_end_time
                                ? Carbon::parse($schedule->schedule->break_end_time)->format('H:i')
                                : null,
                        ];
                    }
                }

                $weeklySchedule[] = $dayInfo;
                $currentDay->addDay();
            }

            return $this->successResponse('Horario semanal generado con éxito', $weeklySchedule);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al generar el horario semanal: ' . $e->getMessage(), 500);
        }
    }

    private function getActiveSchedule(int $employeeId, Carbon $date)
    {
        return EmployeeSchedule::with('schedule')
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date') // Horarios indefinidos
                    ->orWhere('end_date', '>=', $date->toDateString());
            })
            ->where('start_date', '<=', $date->toDateString())
            ->first();
    }

    private function getDayName(int $dayOfWeek): string
    {
        $days = [
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
        ];

        return $days[$dayOfWeek] ?? 'Desconocido';
    }
}
