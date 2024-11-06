<?php

namespace App\Services\Employee\Schedules;

use App\Models\Employee\Schedules\WorkSchedule;
use Illuminate\Http\JsonResponse;
use App\Services\ResponseService;

class WorkScheduleService extends ResponseService
{
    public function getWorkSchedules(int $employee_id): JsonResponse
    {
        try {
            $workSchedules = WorkSchedule::where('employee_id', $employee_id)->get();
            return $this->successResponse('Horarios de trabajo obtenidos con éxito', $workSchedules);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener los horarios de trabajo', 500);
        }
    }

    public function createWorkSchedule(int $employee_id, array $data): JsonResponse
    {
        try {
            $scheduleData = array_merge($data, ['employee_id' => $employee_id]);
            $workSchedule = WorkSchedule::create($scheduleData);
            return $this->successResponse('Horario de trabajo creado con éxito', $workSchedule, 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear el horario de trabajo. Error: ' . $e->getMessage(), 500);
        }
    }

    public function createMultipleWorkSchedules(int $employee_id, array $schedules): JsonResponse
    {
        try {
            foreach ($schedules as $schedule) {
                $scheduleData = array_merge($schedule, ['employee_id' => $employee_id]);
                WorkSchedule::create($scheduleData);
            }
            return $this->successResponse('Horarios de trabajo creados con éxito');
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear el horario de trabajo. Error: ' . $e->getMessage(), 500);
        }
    }


    public function updateWorkSchedule(int $employee_id, int $work_schedule_id, array $data): JsonResponse
    {
        try {
            $workSchedule = WorkSchedule::where('employee_id', $employee_id)->findOrFail($work_schedule_id);
            $workSchedule->update($data);
            return $this->successResponse('Horario de trabajo actualizado con éxito', $workSchedule);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo actualizar el horario de trabajo. Error: ' . $e->getMessage(), 500);
        }
    }



    public function getWorkScheduleById(int $employee_id, string $id): JsonResponse
    {
        try {
            $workSchedule = WorkSchedule::where('employee_id', $employee_id)->findOrFail($id);
            return $this->successResponse('Detalles del horario de trabajo obtenidos con éxito', $workSchedule);
        } catch (\Exception $e) {
            return $this->errorResponse('Horario de trabajo no encontrado', 404);
        }
    }

    public function updateMultipleWorkSchedules(int $employee_id, array $schedules): JsonResponse
    {
        try {
            foreach ($schedules as $schedule) {
                $workSchedule = WorkSchedule::where('employee_id', $employee_id)
                    ->where('id', $schedule['id'])
                    ->firstOrFail();

                $workSchedule->update($schedule);
            }
            return $this->successResponse('Horarios de trabajo actualizados con éxito');
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo actualizar los horarios de trabajo. Error: ' . $e->getMessage(), 500);
        }
    }


    public function deleteWorkSchedule(int $employee_id, string $id): JsonResponse
    {
        try {
            $workSchedule = WorkSchedule::where('employee_id', $employee_id)->findOrFail($id);
            $workSchedule->delete();
            return $this->successResponse('Horario de trabajo eliminado con éxito');
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo eliminar el horario de trabajo', 500);
        }
    }
}
