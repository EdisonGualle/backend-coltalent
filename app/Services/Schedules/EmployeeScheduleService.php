<?php

namespace App\Services\Schedules;

use App\Models\Schedules\EmployeeSchedule;
use App\Models\Contracts\Contract;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class EmployeeScheduleService extends ResponseService
{
    /**
     * Obtener todas las asignaciones de horarios (con o sin eliminadas).
     */
    public function getAllEmployeeSchedules(bool $includeDeleted = false): JsonResponse
    {
        try {
            $query = EmployeeSchedule::with([
                'employee.position.unit.direction',
                'schedule' => function ($query) {
                    $query->withTrashed();
                },
            ]);

            if ($includeDeleted) {
                $query->withTrashed(); // Incluye asignaciones eliminadas lógicamente
            }

            // Ordenar primero por el estado (activos primero) y luego por ID (más alto primero)
            $assignments = $query->orderBy('is_active', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            $formattedAssignments = $assignments->map(
                fn(EmployeeSchedule $assignment) => $this->formatEmployeeSchedule($assignment)
            );

            return $this->successResponse('Lista de asignaciones obtenida con éxito.', $formattedAssignments);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de asignaciones: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Obtener las asignaciones activas de un empleado.
     */
    public function getActiveSchedulesByEmployee(int $employeeId): JsonResponse
    {
        try {
            $activeSchedules = EmployeeSchedule::where('employee_id', $employeeId)
                ->where('is_active', true)
                ->with(['employee.position.unit.direction', 'schedule'])
                ->get()
                ->map(fn($assignment) => $this->formatEmployeeSchedule($assignment));

            return $this->successResponse('Horarios activos obtenidos con éxito.', $activeSchedules);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener los horarios activos: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Asignar un nuevo horario a un empleado.
     */
    public function createEmployeeSchedule(int $employeeId, array $data): JsonResponse
    {
        try {
            // Obtener contrato activo
            $contract = Contract::where('employee_id', $employeeId)
                ->where('is_active', true)
                ->firstOrFail();

            $contractStartDate = $contract->start_date;
            $contractEndDate = $contract->end_date; // Puede ser null para contratos indefinidos
            $today = now()->toDateString();

            // Configurar fechas automáticas para el nuevo horario
            $data['start_date'] = $data['start_date'] ?? ($today >= $contractStartDate ? $today : $contractStartDate);
            $data['end_date'] = $data['end_date'] ?? $contractEndDate;

            // Validar que las fechas sean futuras
            if ($data['start_date'] < $today) {
                return response()->json([
                    'status' => false,
                    'errors' => [
                        'start_date' => ['La fecha de inicio debe ser futura o igual al día actual.']
                    ],
                ], 422);
            }

            // Validar que end_date sea mayor a start_date
            if ($data['end_date'] && $data['end_date'] < $data['start_date']) {
                return response()->json([
                    'status' => false,
                    'errors' => [
                        'end_date' => ['La fecha de fin debe ser posterior a la fecha de inicio.']
                    ],
                ], 422);
            }

            // Validar que si se proporciona end_date, start_date sea obligatorio
            if (isset($data['end_date']) && empty($data['start_date'])) {
                return response()->json([
                    'status' => false,
                    'errors' => [
                        'start_date' => ['Debe proporcionar la fecha de inicio si establece una fecha de fin.']
                    ],
                ], 422);
            }

            // Obtener el horario activo actual
            $currentSchedule = EmployeeSchedule::where('employee_id', $employeeId)
                ->where('is_active', true)
                ->first();

            $updatedCurrentSchedule = null;

            if ($currentSchedule) {
                // Validar si el nuevo horario tiene el mismo schedule_id que el activo
                if ($currentSchedule->schedule_id === $data['schedule_id']) {
                    return response()->json([
                        'status' => false,
                        'errors' => [
                            'schedule_id' => ['El horario ya está asignado. No se requiere ningún cambio.']
                        ],
                    ], 422);
                }

                // Actualizar el horario activo para poner is_active en false
                $currentSchedule->update(['is_active' => false]);

                // Caso 1: Nuevo horario comienza antes del inicio del horario actual
                if ($data['start_date'] < $currentSchedule->start_date) {
                    $currentSchedule->update([
                        'start_date' => $data['start_date'],
                        'end_date' => $data['start_date']
                    ]);
                }
                // Caso 2: Nuevo horario comienza antes de que termine el actual
                elseif ($currentSchedule->end_date && $data['start_date'] < $currentSchedule->end_date) {
                    $currentSchedule->update([
                        'end_date' => $data['start_date']
                    ]);
                }
                // Caso 3: Horario actual tiene end_date = null
                elseif ($currentSchedule->end_date === null) {
                    $currentSchedule->update([
                        'end_date' => $data['start_date']
                    ]);
                }

                $updatedCurrentSchedule = $this->formatEmployeeSchedule($currentSchedule);
            }

            // Crear nueva asignación
            $assignment = EmployeeSchedule::create([
                'employee_id' => $employeeId,
                'schedule_id' => $data['schedule_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_active' => true,
            ]);

            // Recargar para incluir la relación 'schedule'
            $assignment->load('schedule');

            $formattedNewAssignment = $this->formatEmployeeSchedule($assignment);

            // Respuesta con ambos horarios
            return $this->successResponse('Horario asignado con éxito.', [
                'updated_current_schedule' => $updatedCurrentSchedule,
                'new_schedule' => $formattedNewAssignment,
            ], 201);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Empleado o contrato no encontrado.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al asignar el horario: ' . $e->getMessage(), 500);
        }
    }



    /**
     * Cambiar el horario activo de un empleado.
     */
    public function changeEmployeeSchedule(int $employeeId, array $data): JsonResponse
    {
        try {
            // Validar que el nuevo horario no sea igual al actual
            $currentAssignment = EmployeeSchedule::where('employee_id', $employeeId)
                ->where('is_active', true)
                ->first();

            if ($currentAssignment && $currentAssignment->schedule_id == $data['schedule_id']) {
                return $this->errorResponse('El nuevo horario no puede ser igual al horario actual.', 400);
            }

            return $this->createEmployeeSchedule($employeeId, $data);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al cambiar el horario: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Eliminar lógicamente una asignación.
     */
    public function deleteEmployeeSchedule(int $id): JsonResponse
    {
        try {
            $assignment = EmployeeSchedule::findOrFail($id);

            // Validar si el contrato aún está activo
            // $contract = Contract::where('employee_id', $assignment->employee_id)
            //     ->where('is_active', true)
            //     ->first();

            // if ($contract) {
            //     return $this->errorResponse('No se puede eliminar el horario porque el contrato del empleado aún está activo.', 400);
            // }

            $assignment->delete();

            return $this->successResponse('Asignación eliminada con éxito.', ['id' => $id]);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Asignación no encontrada.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar la asignación: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Restaurar una asignación eliminada.
     */
    public function restoreEmployeeSchedule(int $id): JsonResponse
    {
        try {
            $assignment = EmployeeSchedule::withTrashed()->findOrFail($id);
            $assignment->restore();

            return $this->successResponse('Asignación restaurada con éxito.', $this->formatEmployeeSchedule($assignment));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Asignación no encontrada.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al restaurar la asignación: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Finalizar horarios activos cuando el contrato termina.
     */
    public function finalizeSchedulesOnContractEnd(int $employeeId): void
    {
        EmployeeSchedule::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->update([
                'end_date' => now()->toDateString(),
                'is_active' => false,
            ]);
    }

    /**
     * Formatear una asignación.
     */
    private function formatEmployeeSchedule(EmployeeSchedule $assignment): array
    {
        $employee = $assignment->employee;

        return [
            'id' => $assignment->id,
            'start_date' => $assignment->start_date ?? null,
            'end_date' => $assignment->end_date ?? null,
            'status' => $assignment->is_active ? 'Activo' : 'Inactivo',
            'employee' => [
                'id' => $employee->id,
                'identification' => $employee->identification,
                'full_name' => $employee->full_name,
                'organization' => [
                    'position' => $employee->position->name ?? 'N/A',
                    'unit' => $employee->unit->name ?? 'N/A',
                    'direction' => $employee->finalDirection?->name ?? 'N/A',
                ],
            ],
            'schedule' => [
                'id' => $assignment->schedule?->id,
                'name' => $assignment->schedule?->name,
            ],
        ];
    }

}
