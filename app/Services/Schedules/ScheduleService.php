<?php

namespace App\Services\Schedules;

use App\Models\Schedules\Schedule;
use App\Services\ResponseService;
use App\Utilities\TimeFormatter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class ScheduleService extends ResponseService
{
    /**
     * Obtener todos los horarios.
     */
    public function getAllSchedules(bool $includeDeleted = false): JsonResponse
    {
        try {
            $query = Schedule::query();

            if ($includeDeleted) {
                $query->withTrashed();
            }

            $schedules = $query->get()->map(function (Schedule $schedule) {
                return $this->formatSchedule($schedule);
            });

            return $this->successResponse('Lista de horarios obtenida con éxito.', $schedules);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de horarios: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Obtener los detalles de un horario específico.
     */
    public function getScheduleById(int $id): JsonResponse
    {
        try {
            $schedule = Schedule::findOrFail($id);
            return $this->successResponse('Detalles del horario obtenidos con éxito.', $this->formatSchedule($schedule));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Horario no encontrado.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener el horario: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Crear un nuevo horario.
     */
    public function createSchedule(array $data): JsonResponse
    {
        try {
            $schedule = Schedule::create($data);
            return $this->successResponse('Horario creado con éxito.', $this->formatSchedule($schedule), 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear el horario: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Actualizar un horario existente.
     */
    public function updateSchedule(int $id, array $data): JsonResponse
    {
        try {
            $schedule = Schedule::findOrFail($id);
            $schedule->update($data);
            return $this->successResponse('Horario actualizado con éxito.', $this->formatSchedule($schedule));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Horario no encontrado.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar el horario: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Eliminar lógicamente un horario.
     */
    public function deleteSchedule(int $id): JsonResponse
    {
        try {
            $schedule = Schedule::findOrFail($id);
            $schedule->delete();

            // Devolver el horario con formato, incluyendo el estado actualizado
            return $this->successResponse('Horario eliminado con éxito.', $this->formatSchedule($schedule));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Horario no encontrado.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar el horario: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Restaurar un horario eliminado lógicamente.
     */
    public function restoreSchedule(int $id): JsonResponse
    {
        try {
            $schedule = Schedule::withTrashed()->findOrFail($id);

            if (!$schedule->trashed()) {
                return $this->errorResponse('El horario no está eliminado.', 400);
            }

            $schedule->restore();

            return $this->successResponse('Horario restaurado con éxito.', $this->formatSchedule($schedule));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Horario no encontrado.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al restaurar el horario: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Formatear un horario.
     */
    private function formatSchedule(Schedule $schedule): array
    {
        $weeklyMinutes = $this->calculateWeeklyMinutes($schedule);

        return [
            'id' => $schedule->id,
            'name' => $schedule->name,
            'start_time' => $this->formatTime($schedule->start_time),
            'end_time' => $this->formatTime($schedule->end_time),
            'break_start_time' => $this->formatTime($schedule->break_start_time),
            'break_end_time' => $this->formatTime($schedule->break_end_time),
            'rest_days' => $schedule->rest_days,
            'status' => $schedule->deleted_at ? 'Inactivo' : 'Activo',
            'weekly_hours' => TimeFormatter::formatMinutesToReadable($weeklyMinutes),
        ];
    }

    /**
     * Calcular los minutos semanales efectivos de un horario.
     */
    private function calculateWeeklyMinutes(Schedule $schedule): int
    {
        try {
            // Formatear las horas utilizando formatTime
            $startTime = $this->formatTime($schedule->start_time);
            $endTime = $this->formatTime($schedule->end_time);

            // Validar horas de inicio y fin
            if (!$startTime || !$endTime) {
                return 0; // Si no hay horas de inicio o fin, los minutos semanales son 0
            }

            // Crear instancias de Carbon
            $startTime = Carbon::createFromFormat('H:i', $startTime);
            $endTime = Carbon::createFromFormat('H:i', $endTime);

            // Manejar horarios cruzados al siguiente día
            if ($endTime->lt($startTime)) {
                $endTime->addDay();
            }

            // Calcular minutos diarios entre inicio y fin
            $dailyMinutes = $endTime->diffInMinutes($startTime);

            // Manejar descanso (opcional)
            $breakStartTime = $this->formatTime($schedule->break_start_time);
            $breakEndTime = $this->formatTime($schedule->break_end_time);
            $breakDuration = 0;

            if ($breakStartTime && $breakEndTime) {
                $breakStartTime = Carbon::createFromFormat('H:i', $breakStartTime);
                $breakEndTime = Carbon::createFromFormat('H:i', $breakEndTime);

                // Manejar descanso cruzado al siguiente día
                if ($breakEndTime->lt($breakStartTime)) {
                    $breakEndTime->addDay();
                }

                $breakDuration = $breakEndTime->diffInMinutes($breakStartTime);
            }

            // Cálculo efectivo diario
            $effectiveDailyMinutes = max($dailyMinutes - $breakDuration, 0);

            // Días laborales en la semana (7 días - días de descanso)
            $workDays = 7 - count($schedule->rest_days ?? []);

            // Cálculo total semanal
            return $effectiveDailyMinutes * $workDays;
        } catch (\Exception $e) {
            // Registrar errores y devolver 0 minutos en caso de fallo
            return 0;
        }
    }

    /**
     * Formatear tiempo (cortar a hh:mm o devolver null si es nulo).
     */
    private function formatTime(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }
}
