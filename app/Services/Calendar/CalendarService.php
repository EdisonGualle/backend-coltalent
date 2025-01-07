<?php

namespace App\Services\Calendar;

use App\Models\Schedules\EmployeeSchedule;
use App\Models\Holidays\Holiday;
use App\Models\Holidays\HolidayAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class CalendarService extends ResponseService
{
    public function generateEmployeeCalendar(int $employeeId): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Obtener el número de días desde la configuración
            $days = DB::table('configurations')
                ->where('key', 'max_days_for_leave')
                ->value('value') ?? 30;

            $calendar = [];
            $startDate = Carbon::now();
            $endDate = Carbon::now()->addDays($days);
            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {
                $dayInfo = [
                    'date' => $currentDate->toDateString(),
                    'type' => null,
                    'reason' => null,
                    'work_schedule' => null,
                ];

                // 1. Verificar si es festivo general o asignado
                $holiday = $this->getHolidayForDate($currentDate, $employeeId);
                if ($holiday) {
                    $dayInfo['type'] = 'holiday';
                    $dayInfo['reason'] = $holiday['name'];
                } else {
                    // 2. Verificar si es un día de descanso o laboral
                    $schedule = $this->getActiveSchedule($employeeId, $currentDate);

                    if (!$schedule) {
                        $dayInfo['type'] = 'no_schedule';
                        $dayInfo['reason'] = 'Sin horario asignado';
                    } else {
                        $restDays = $schedule->schedule->rest_days;
                        if (in_array($currentDate->dayOfWeek, $restDays)) {
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
                }

                $calendar[] = $dayInfo;
                $currentDate->addDay();
            }

            DB::commit();

            return $this->successResponse('Calendario generado con éxito', $calendar);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al generar el calendario: ' . $e->getMessage(), 500);
        }
    }

    private function getHolidayForDate(Carbon $date, int $employeeId): ?array
    {
        // Verificar si es un día festivo general
        $holiday = Holiday::where(function ($query) use ($date) {
            $query->where('is_recurring', true)
                ->whereMonth('date', $date->month)
                ->whereDay('date', $date->day)
                ->orWhere('date', $date->toDateString());
        })
            ->where('applies_to_all', true)
            ->first();

        if ($holiday) {
            return ['name' => $holiday->name];
        }

        // Verificar si es un día festivo asignado al empleado
        $assignedHoliday = HolidayAssignment::where('employee_id', $employeeId)
            ->whereHas('holiday', function ($query) use ($date) {
                $query->where('is_recurring', true)
                    ->whereMonth('date', $date->month)
                    ->whereDay('date', $date->day)
                    ->orWhere('date', $date->toDateString());
            })
            ->first();

        if ($assignedHoliday) {
            return ['name' => $assignedHoliday->holiday->name];
        }

        return null;
    }

    private function getActiveSchedule(int $employeeId, Carbon $date)
    {
        return EmployeeSchedule::with('schedule')
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date->toDateString());
            })
            ->where('start_date', '<=', $date->toDateString())
            ->first();
    }
}
