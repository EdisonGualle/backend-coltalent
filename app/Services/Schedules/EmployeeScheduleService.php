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
            $query = EmployeeSchedule::with(['employee.position.unit.direction', 'schedule']);
    
            if ($includeDeleted) {
                $query->withTrashed();
            }
    
            $assignments = $query->get();
    
            $formattedAssignments = $assignments->map(
                fn(EmployeeSchedule $assignment) =>
                $this->formatEmployeeSchedule($assignment)
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
    
            // Verificar si existen asignaciones previas
            $hasPreviousAssignments = EmployeeSchedule::where('employee_id', $employeeId)->exists();
    
            // Configurar fechas predeterminadas
            if (!$hasPreviousAssignments) {
                // Primera asignación
                $data['start_date'] = $data['start_date'] ?? $contractStartDate;
                $data['end_date'] = $data['end_date'] ?? null;
            } else {
                // Asignaciones posteriores
                $data['start_date'] = $data['start_date'] ?? $today;
                $data['end_date'] = $data['end_date'] ?? null;
            }
    
            // Validaciones de fechas utilizando Validator
            $validator = Validator::make($data, [
                'start_date' => [
                    'required',
                    'date',
                    'after_or_equal:' . $contractStartDate,
                    $contractEndDate ? 'before_or_equal:' . $contractEndDate : '',
                ],
                'end_date' => [
                    'nullable',
                    'date',
                    'after:start_date',
                    $contractEndDate ? 'before_or_equal:' . $contractEndDate : '',
                ],
            ], [
                'start_date.required' => 'La fecha de inicio es obligatoria.',
                'start_date.after_or_equal' => 'La fecha de inicio no puede ser anterior a la fecha de inicio del contrato.',
                'start_date.before_or_equal' => 'La fecha de inicio no puede ser posterior a la fecha de fin del contrato.',
                'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
                'end_date.before_or_equal' => 'La fecha de fin no puede ser posterior a la fecha de fin del contrato.',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }
    
            // Validar solapamiento de horarios anteriores
            $overlappingSchedule = EmployeeSchedule::where('employee_id', $employeeId)
                ->where(function ($query) use ($data) {
                    $query->whereBetween('start_date', [$data['start_date'], $data['end_date'] ?? now()])
                        ->orWhereBetween('end_date', [$data['start_date'], $data['end_date'] ?? now()])
                        ->orWhere(function ($query) use ($data) {
                            $query->where('start_date', '<=', $data['start_date'])
                                ->where('end_date', '>=', $data['end_date'] ?? now());
                        });
                })
                ->exists();
    
            if ($overlappingSchedule) {
                return response()->json([
                    'status' => false,
                    'errors' => [
                        'start_date' => ['El nuevo horario se solapa con un horario ya existente.'],
                        'end_date' => ['El nuevo horario se solapa con un horario ya existente.'],
                    ],
                ], 422);
            }
    
            // Finalizar asignación activa actual
            $currentAssignment = EmployeeSchedule::where('employee_id', $employeeId)
                ->where('is_active', true)
                ->first();
    
            if ($currentAssignment) {
                if ($currentAssignment->end_date && $currentAssignment->end_date > $data['start_date']) {
                    $currentAssignment->update([
                        'end_date' => $data['start_date'],
                        'is_active' => false,
                    ]);
                } else {
                    $currentAssignment->update([
                        'end_date' => $today,
                        'is_active' => false,
                    ]);
                }
            }
    
            // Crear nueva asignación
            $assignment = EmployeeSchedule::create([
                'employee_id' => $employeeId,
                'schedule_id' => $data['schedule_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_active' => true,
            ]);
    
            return $this->successResponse('Horario asignado con éxito.', $this->formatEmployeeSchedule($assignment), 201);
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
            $contract = Contract::where('employee_id', $assignment->employee_id)
                ->where('is_active', true)
                ->first();

            if ($contract) {
                return $this->errorResponse('No se puede eliminar el horario porque el contrato del empleado aún está activo.', 400);
            }

            $assignment->delete();

            return $this->successResponse('Asignación eliminada con éxito.');
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
                    'position' => $employee->position->name ?? 'No especificado',
                    'unit' => $employee->unit->name ?? 'No especificado',
                    'direction' => $employee->finalDirection?->name ?? 'No especificado',
                ],
            ],
            'schedule' => [
                'id' => $assignment->schedule?->id,
                'name' => $assignment->schedule?->name,
            ],
        ];
    }
    
}
