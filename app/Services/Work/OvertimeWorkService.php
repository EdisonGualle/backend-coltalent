<?php

namespace App\Services\Work;

use App\Models\Work\OvertimeWork;
use App\Models\Employee\Employee;
use Illuminate\Http\JsonResponse;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OvertimeWorkService extends ResponseService
{
    /**
     * Crear un registro de trabajo.
     */
    /**
     * Crear un registro de trabajo.
     */
    public function createWorkRecord(array $data): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Validar que el empleado existe
            $employee = Employee::find($data['employee_id']);
            if (!$employee) {
                return $this->errorResponse("El empleado no existe.", 400);
            }

            $date = Carbon::parse($data['date']);
            $startTime = Carbon::parse($data['start_time']);
            $endTime = Carbon::parse($data['end_time']);
            $breakStartTime = isset($data['break_start_time']) ? Carbon::parse($data['break_start_time']) : null;
            $breakEndTime = isset($data['break_end_time']) ? Carbon::parse($data['break_end_time']) : null;

            // Validar que no haya un registro para la misma fecha
            $exists = OvertimeWork::where('employee_id', $data['employee_id'])
                ->where('date', $data['date'])
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                return $this->errorResponse("Ya existe un registro para esta fecha.", 400);
            }

            // Verificar horario activo del empleado
            $schedule = DB::table('employee_schedules')
                ->join('schedules', 'employee_schedules.schedule_id', '=', 'schedules.id')
                ->where('employee_schedules.employee_id', $data['employee_id'])
                ->where('employee_schedules.is_active', true)
                ->whereDate('employee_schedules.start_date', '<=', $date)
                ->where(function ($query) use ($date) {
                    $query->whereNull('employee_schedules.end_date')
                        ->orWhereDate('employee_schedules.end_date', '>=', $date);
                })
                ->first();

            if (!$schedule) {
                return $this->errorResponse(
                    'El empleado no tiene un horario laboral activo asignado para esta fecha.',
                    400
                );
            }

            $workStart = Carbon::parse($schedule->start_time);
            $workEnd = Carbon::parse($schedule->end_time);

            // Determinar si es un día festivo
            $holidayInfo = $this->isHoliday($date, $data['employee_id']);

            if ($holidayInfo) {
                Log::info("Procesando día festivo: " . json_encode($holidayInfo));
                $workedValue = $this->validateAndCalculateForHoliday(
                    $startTime,
                    $endTime,
                    $schedule,
                    $data,
                    $breakStartTime,
                    $breakEndTime,
                    $holidayInfo['holiday']
                );
            } else {
                Log::info("No es día festivo, procediendo con días de descanso o horas extras.");

                // Determinar si es día de descanso
                $restDays = json_decode($schedule->rest_days, true);
                $isRestDay = in_array($date->dayOfWeek, $restDays);

                if ($isRestDay) {
                    Log::info("Es día de descanso.");
                    $workedValue = $this->validateAndCalculateForRestDay(
                        $startTime,
                        $endTime,
                        $schedule,
                        $data,
                        $breakStartTime,
                        $breakEndTime
                    );
                } else {
                    Log::info("Es un día normal, procesando horas extras.");
                    $workedValue = $this->validateAndCalculateForOvertime(
                        $startTime,
                        $endTime,
                        $workStart,
                        $workEnd,
                        $schedule,
                        $data
                    );
                }
            }

            // Guardar el registro
            $workRecord = OvertimeWork::create([
                'employee_id' => $data['employee_id'],
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'break_start_time' => $data['break_start_time'] ?? null,
                'break_end_time' => $data['break_end_time'] ?? null,
                'worked_value' => $workedValue,
                'reason' => $data['reason'],
                'generates_compensatory' => $data['generates_compensatory'] ?? true,
            ]);

            DB::commit();
            return $this->successResponse('Registro creado correctamente.', $this->formatWorkRecord($workRecord));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al crear el registro: ' . $e->getMessage(), 400);
        }
    }





    private function isHoliday(Carbon $date, int $employeeId): ?array
    {
        Log::info("Validando si la fecha es festiva: " . $date->format('Y-m-d'));

        // Verificar si es día festivo aplicado a todos
        $generalHoliday = DB::table('holidays')
            ->where('applies_to_all', true)
            ->where(function ($query) use ($date) {
                $query->where('is_recurring', true)
                    ->whereRaw("MONTH(date) = ? AND DAY(date) = ?", [$date->month, $date->day]) // Solo coinciden día y mes
                    ->orWhere('date', $date->format('Y-m-d')); // Fecha exacta
            })
            ->whereNull('deleted_at')
            ->first();

        if ($generalHoliday) {
            Log::info("Se encontró un día festivo general: " . json_encode($generalHoliday));
            return [
                'type' => 'general',
                'holiday' => $generalHoliday
            ];
        }

        // Verificar si es día festivo asignado al empleado
        $assignedHoliday = DB::table('holiday_assignments')
            ->join('holidays', 'holiday_assignments.holiday_id', '=', 'holidays.id')
            ->where('holiday_assignments.employee_id', $employeeId)
            ->where(function ($query) use ($date) {
                $query->where('holidays.is_recurring', true)
                    ->whereRaw("MONTH(holidays.date) = ? AND DAY(holidays.date) = ?", [$date->month, $date->day]) // Día y mes recurrentes
                    ->orWhere('holidays.date', $date->format('Y-m-d')); // Fecha exacta
            })
            ->whereNull('holidays.deleted_at')
            ->whereNull('holiday_assignments.deleted_at')
            ->first();

        if ($assignedHoliday) {
            Log::info("Se encontró un día festivo asignado al empleado: " . json_encode($assignedHoliday));
            return [
                'type' => 'assigned',
                'holiday' => $assignedHoliday
            ];
        }

        Log::info("La fecha no es festiva.");
        return null; // No es un día festivo
    }




    private function validateAndCalculateForHoliday(
        $startTime,
        $endTime,
        $schedule,
        $data,
        $breakStartTime = null,
        $breakEndTime = null,
        $holiday = null
    ) {
        $configurations = DB::table('configurations')
            ->where('category', 'Trabajo Extra')
            ->pluck('value', 'key');

        $minHolidayHours = (int) $configurations['holiday_min_hours'];
        $maxHolidayHours = (int) $configurations['holiday_max_hours'];
        $minBreakDuration = (int) $configurations['min_break_duration'];
        $maxBreakDuration = (int) $configurations['max_break_duration'];

        $workStart = Carbon::parse($schedule->start_time);
        $workEnd = Carbon::parse($schedule->end_time);

        if ($startTime->lt($workStart) || $endTime->gt($workEnd)) {
            throw new \Exception(
                "Las horas trabajadas en días festivos deben estar dentro del horario base definido: {$workStart->format('H:i')} - {$workEnd->format('H:i')}."
            );
        }

        $workedHours = $this->calculateEffectiveHours(
            $startTime,
            $endTime,
            $schedule,
            true,
            false,
            $breakStartTime,
            $breakEndTime
        );

        if ($breakStartTime && $breakEndTime) {
            $breakDuration = Carbon::parse($breakStartTime)->diffInMinutes(Carbon::parse($breakEndTime)) / 60;

            if ($breakDuration < $minBreakDuration || $breakDuration > $maxBreakDuration) {
                throw new \Exception(
                    "La duración del descanso debe estar entre {$minBreakDuration} y {$maxBreakDuration} horas."
                );
            }
        }

        if ($workedHours < $minHolidayHours || $workedHours > $maxHolidayHours) {
            throw new \Exception(
                "Las horas trabajadas en días festivos deben estar entre {$minHolidayHours} y {$maxHolidayHours} horas."
            );
        }

        return $workedHours;
    }





    private function validateAndCalculateForRestDay(
        $startTime,
        $endTime,
        $schedule,
        $data,
        $breakStartTime = null,
        $breakEndTime = null
    ) {
        $configurations = DB::table('configurations')
            ->where('category', 'Trabajo Extra')
            ->pluck('value', 'key');

        $minRestDayHours = (int) $configurations['rest_day_min_hours'];
        $maxRestDayHours = (int) $configurations['rest_day_max_hours'];
        $minBreakDuration = (int) $configurations['min_break_duration'];
        $maxBreakDuration = (int) $configurations['max_break_duration'];

        $workStart = Carbon::parse($schedule->start_time);
        $workEnd = Carbon::parse($schedule->end_time);

        if ($startTime->lt($workStart) || $endTime->gt($workEnd)) {
            throw new \Exception(
                "Las horas trabajadas en días de descanso deben estar dentro del horario base definido: {$workStart->format('H:i')} - {$workEnd->format('H:i')}."
            );
        }

        $workedHours = $this->calculateEffectiveHours(
            $startTime,
            $endTime,
            $schedule,
            false,
            true,
            $breakStartTime,
            $breakEndTime
        );

        if ($breakStartTime && $breakEndTime) {
            $breakDuration = Carbon::parse($breakStartTime)->diffInMinutes(Carbon::parse($breakEndTime)) / 60;

            if ($breakDuration < $minBreakDuration || $breakDuration > $maxBreakDuration) {
                throw new \Exception(
                    "La duración del descanso debe estar entre {$minBreakDuration} y {$maxBreakDuration} horas."
                );
            }
        }

        if ($workedHours < $minRestDayHours || $workedHours > $maxRestDayHours) {
            throw new \Exception(
                "Las horas trabajadas en días de descanso deben estar entre {$minRestDayHours} y {$maxRestDayHours} horas."
            );
        }

        return $workedHours;
    }


    private function validateAndCalculateForOvertime($startTime, $endTime, $workStart, $workEnd, $schedule, $data)
    {
        $configurations = DB::table('configurations')
            ->where('category', 'Trabajo Extra')
            ->pluck('value', 'key');

        $minOvertimeHours = (int) $configurations['overtime_min_hours'];
        $maxOvertimeHours = (int) $configurations['overtime_max_hours'];

        // Verificar que no se proporcionen horas de descanso
        if (isset($data['break_start_time']) || isset($data['break_end_time'])) {
            throw new \Exception("Las horas de descanso no aplican para registros de horas extras.");
        }
        
        if ($startTime->lt($workEnd)) {
            throw new \Exception("Las horas extras deben comenzar después del horario laboral: {$workEnd->format('H:i')}.");
        }

        // Llamada ajustada a calculateEffectiveHours
        $workedHours = $this->calculateEffectiveHours($startTime, $endTime, $schedule, false, false);

        if ($workedHours < $minOvertimeHours || $workedHours > $maxOvertimeHours) {
            throw new \Exception("Las horas extras deben estar entre {$minOvertimeHours} y {$maxOvertimeHours} horas.");
        }

        return $workedHours;
    }


    /**
     * Calcular las horas trabajadas efectivas.
     */
    private function calculateEffectiveHours(Carbon $startTime, Carbon $endTime, $schedule, bool $isHoliday, bool $isRestDay, ?Carbon $breakStart = null, ?Carbon $breakEnd = null): float
    {
        $totalHours = $startTime->diffInMinutes($endTime) / 60;

        // Configuración
        $configurations = DB::table('configurations')
            ->where('category', 'Trabajo Extra')
            ->pluck('value', 'key');

        $maxConsecutiveHours = (float) $configurations['max_consecutive_hours'];
        $minConsecutiveHours = (float) $configurations['min_consecutive_hours'];
        $minBreakDuration = (float) $configurations['min_break_duration'];
        $maxBreakDuration = (float) $configurations['max_break_duration'];

        // Validar horas consecutivas si no hay descanso
        if (!$breakStart || !$breakEnd) {
            if ($totalHours > $maxConsecutiveHours) {
                throw new \Exception("No se permite trabajar más de {$maxConsecutiveHours} horas consecutivas sin descanso.");
            }
            if ($totalHours < $minConsecutiveHours) {
                throw new \Exception("El tiempo trabajado debe ser al menos de {$minConsecutiveHours} horas consecutivas.");
            }
        }

        // Validar y descontar tiempo de descanso si se proporciona
        if ($breakStart && $breakEnd) {
            $breakDuration = $breakStart->diffInMinutes($breakEnd) / 60;

            if ($breakDuration < $minBreakDuration || $breakDuration > $maxBreakDuration) {
                throw new \Exception("La duración del descanso debe estar entre {$minBreakDuration} y {$maxBreakDuration} horas.");
            }

            // Restar las horas de descanso del total
            if ($breakStart->lt($endTime) && $breakEnd->gt($startTime)) {
                $overlapStart = $breakStart->max($startTime);
                $overlapEnd = $breakEnd->min($endTime);
                $overlapDuration = $overlapStart->diffInMinutes($overlapEnd) / 60;
                $totalHours -= $overlapDuration;
            }
        }

        // Validar el total de horas resultantes
        if ($totalHours < 0) {
            throw new \Exception("El tiempo total trabajado no puede ser negativo después de restar el descanso.");
        }

        return max($totalHours, 0);
    }


    /**
     * Obtener todos los registros activos.
     */
    public function getAllActiveWorkRecords(): JsonResponse
    {
        try {
            $records = OvertimeWork::with('employee')
                ->whereNull('deleted_at') // Solo registros activos
                ->get();

            $formattedRecords = $records->map(fn($record) => $this->formatWorkRecord($record));

            return $this->successResponse('Registros obtenidos con éxito.', $formattedRecords);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener los registros: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener registros activos de un empleado específico.
     */
    public function getActiveWorkRecordsByEmployee(int $employeeId): JsonResponse
    {
        try {
            $records = OvertimeWork::with('employee')
                ->where('employee_id', $employeeId)
                ->whereNull('deleted_at') // Solo registros activos
                ->get();

            if ($records->isEmpty()) {
                return $this->successResponse("No hay registros disponibles.", []);
            }

            $formattedRecords = $records->map(fn($record) => $this->formatWorkRecord($record));

            return $this->successResponse("Registros obtenidos con éxito.", $formattedRecords);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener los registros: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Obtener un registro específico.
     */
    public function getWorkRecordById(int $recordId): JsonResponse
    {
        try {
            $record = OvertimeWork::with('employee')->find($recordId);

            if (!$record || $record->deleted_at) {
                return $this->errorResponse("El registro no existe o ha sido eliminado.", 404);
            }

            return $this->successResponse('Registro obtenido con éxito.', $this->formatWorkRecord($record));
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener el registro: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Eliminar registros de trabajo en rango.
     */
    public function deleteWorkRecords(array $recordIds): JsonResponse
    {
        try {
            DB::beginTransaction();

            $records = OvertimeWork::whereIn('id', $recordIds)
                ->whereNull('deleted_at') // Solo registros activos
                ->get();

            if ($records->isEmpty()) {
                return $this->errorResponse('No se encontraron registros activos para eliminar.', 400);
            }

            foreach ($records as $record) {
                $workDate = Carbon::parse($record->date);
                if ($workDate->isToday() || $workDate->isPast()) {
                    throw new \Exception("No se pueden eliminar registros de fechas pasadas o actuales: {$record->date}.");
                }

                $record->delete(); // Eliminación lógica
            }

            DB::commit();
            return $this->successResponse("Registros eliminados correctamente: {$records->count()} registros afectados.");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al eliminar los registros: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Formatear un registro de trabajo para la respuesta.
     */
    private function formatWorkRecord(OvertimeWork $workRecord): array
    {
        return [
            'id' => $workRecord->id,
            'employee' => $workRecord->employee->only(['id', 'first_name', 'last_name']),
            'date' => $workRecord->date,
            'start_time' => $workRecord->start_time,
            'end_time' => $workRecord->end_time,
            'worked_value' => $workRecord->worked_value,
            'reason' => $workRecord->reason,
            'generates_compensatory' => $workRecord->generates_compensatory,
            'created_at' => $workRecord->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
