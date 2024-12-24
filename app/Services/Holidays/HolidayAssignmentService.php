<?php

namespace App\Services\Holidays;

use App\Models\Holidays\HolidayAssignment;
use App\Models\Holidays\Holiday;
use App\Models\Employee\Employee;
use Illuminate\Http\JsonResponse;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;

class HolidayAssignmentService extends ResponseService
{
    /**
     * Crear asignaciones de días festivos para múltiples empleados.
     */
    public function createAssignments(int $holidayId, array $employeeIds): JsonResponse
    {
        try {
            DB::beginTransaction();
    
            $holiday = Holiday::find($holidayId);
            if (!$holiday) {
                return $this->errorResponse("El día festivo con ID {$holidayId} no existe.", 400);
            }
    
            $duplicatedEmployees = [];
            foreach ($employeeIds as $employeeId) {
                $assignmentExists = HolidayAssignment::where('holiday_id', $holidayId)
                    ->where('employee_id', $employeeId)
                    ->exists();
    
                if ($assignmentExists) {
                    $duplicatedEmployees[] = $employeeId;
                    continue;
                }
    
                HolidayAssignment::create([
                    'holiday_id' => $holidayId,
                    'employee_id' => $employeeId,
                ]);
            }
    
            DB::commit();
    
            if (!empty($duplicatedEmployees)) {
                $duplicatedList = implode(', ', $duplicatedEmployees);
                return $this->errorResponse("Los siguientes empleados ya están asignados al día festivo '{$holiday->name}': {$duplicatedList}", 400);
            }
    
            return $this->successResponse("Asignaciones creadas correctamente para el día festivo '{$holiday->name}'.");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al asignar empleados: ' . $e->getMessage(), 400);
        }
    }
    

    /**
     * Obtener todas las asignaciones activas.
     */
    public function getAllAssignments(): JsonResponse
    {
        try {
            $assignments = HolidayAssignment::with(['holiday', 'employee'])
                ->whereNull('deleted_at') // Solo activos
                ->get();

            $formattedAssignments = $assignments->map(fn(HolidayAssignment $assignment) => $this->formatAssignment($assignment));

            return $this->successResponse('Lista de todas las asignaciones activas obtenida con éxito', $formattedAssignments);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener las asignaciones: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener asignaciones activas de un empleado específico.
     */
    public function getAssignmentsByEmployee(int $employeeId): JsonResponse
    {
        try {
            // Validar que el empleado existe
            $employee = Employee::find($employeeId);
            if (!$employee) {
                throw new \Exception("El empleado no existe.");
            }

            $assignments = HolidayAssignment::with(['holiday', 'employee'])
                ->where('employee_id', $employeeId)
                ->get();

            $formattedAssignments = $assignments->map(fn(HolidayAssignment $assignment) => $this->formatAssignment($assignment));

            return $this->successResponse('Asignaciones activas del empleado obtenidas con éxito', $formattedAssignments);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener las asignaciones del empleado: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Eliminar múltiples asignaciones.
     */
    public function deleteAssignments(array $assignmentIds): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Validar que el array no esté vacío
            if (empty($assignmentIds)) {
                throw new \Exception('El array de IDs de asignaciones está vacío.');
            }

            // Ejecutar eliminación lógica directamente
            $deletedCount = HolidayAssignment::whereIn('id', $assignmentIds)
                ->whereNull('deleted_at') // Solo asignaciones activas
                ->update(['deleted_at' => now()]); // Marcarlas como eliminadas

            if ($deletedCount === 0) {
                throw new \Exception('No se encontraron asignaciones activas para eliminar.');
            }

            DB::commit();
            return $this->successResponse("Asignaciones eliminadas correctamente: {$deletedCount} registros afectados.");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al eliminar las asignaciones: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Formatear una asignación para la respuesta.
     */
    private function formatAssignment(HolidayAssignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'holiday' => $assignment->holiday->only(['id', 'name', 'date', 'is_recurring']),
            'employee' => $assignment->employee->only(['id', 'first_name', 'last_name']),
        ];
    }
}
