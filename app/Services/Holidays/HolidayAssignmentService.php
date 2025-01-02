<?php

namespace App\Services\Holidays;

use App\Models\Holidays\HolidayAssignment;
use App\Models\Holidays\Holiday;
use App\Models\Employee\Employee;
use Illuminate\Http\JsonResponse;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

            $successfulAssignments = [];
            $duplicatedEmployees = [];

            foreach ($employeeIds as $employeeId) {
                $assignmentExists = HolidayAssignment::where('holiday_id', $holidayId)
                    ->where('employee_id', $employeeId)
                    ->exists();

                if ($assignmentExists) {
                    $duplicatedEmployees[] = $employeeId;
                    continue;
                }

                $assignment = HolidayAssignment::create([
                    'holiday_id' => $holidayId,
                    'employee_id' => $employeeId,
                ]);

                $successfulAssignments[] = $this->formatAssignment($assignment);
            }

            DB::commit();

            if (count($successfulAssignments) === 0) {
                return $this->errorResponse("Todos los empleados ya están asignados al día festivo '{$holiday->name}'.", 400);
            }

            $message = count($duplicatedEmployees) > 0
                ? "Algunos empleados ya estaban asignados al día festivo '{$holiday->name}', pero se completaron las nuevas asignaciones."
                : "Todas las asignaciones se completaron correctamente para el día festivo '{$holiday->name}'.";

            return $this->successResponse($message, $successfulAssignments);
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
            $assignments = HolidayAssignment::with(['holiday', 'employee.position.unit.direction'])
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

            // Obtener los IDs de asignaciones activas
            $assignmentsToDelete = HolidayAssignment::whereIn('id', $assignmentIds)
                ->whereNull('deleted_at') // Solo asignaciones activas
                ->pluck('id') // Obtener los IDs
                ->toArray();

            // Validar si hay asignaciones para eliminar
            if (empty($assignmentsToDelete)) {
                throw new \Exception('No se encontraron asignaciones activas para eliminar.');
            }

            // Ejecutar la eliminación lógica
            HolidayAssignment::whereIn('id', $assignmentsToDelete)
                ->update(['deleted_at' => now()]);

            DB::commit();

            // Retornar respuesta con los IDs eliminados
            return $this->successResponse(
                count($assignmentsToDelete) === 1
                    ? "Se eliminó 1 asignación correctamente."
                    : "Se eliminaron " . count($assignmentsToDelete) . " asignaciones correctamente.",
                ['ids' => $assignmentsToDelete]
            );
            
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
        $employee = $assignment->employee;
        $holiday = $assignment->holiday;
        return [
            'id' => $assignment->id,
            'holiday' => [
                'id' => $holiday->id,
                'name' => $holiday->name,
                'date' => Carbon::parse($holiday->date)->translatedFormat('j \d\e F \d\e Y'),
                'is_recurring' => $holiday->is_recurring,
            ],
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
        ];
    }
}
