<?php

namespace App\Services\Schedules;

use App\Models\Schedules\Schedule;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

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
            return $this->successResponse('Horario eliminado con éxito.');
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
        return [
            'id' => $schedule->id,
            'name' => $schedule->name,
            'description' => $schedule->description,
            'start_time' => $schedule->start_time,
            'end_time' => $schedule->end_time,
            'break_start_time' => $schedule->break_start_time,
            'break_end_time' => $schedule->break_end_time,
            'rest_days' => $schedule->rest_days,
            'status' => $schedule->deleted_at ? 'Inactivo' : 'Activo',
        ];
    }
}
