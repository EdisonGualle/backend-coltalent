<?php

namespace App\Services\Leave;

use App\Models\Employee\Employee;
use App\Models\Leave\Delegation;
use App\Models\Leave\Leave;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class SubrogationService extends ResponseService
{
    public function getAvailableSubrogates(int $leaveId): JsonResponse
    {
        try {
            // Obtener el permiso solicitado y su rango de fechas
            $leave = Leave::with('employee.position.unit', 'employee.position.direction')->find($leaveId);

            if (!$leave) {
                return $this->errorResponse('Permiso no encontrado.', 404);
            }

            $startDate = $leave->start_date;
            $endDate = $leave->end_date;
            $employeeId = $leave->employee_id;

            // Determinar si el empleado pertenece a una unidad o dirección
            $employeePosition = $leave->employee->position;
            $unitId = $employeePosition->unit ? $employeePosition->unit->id : null;
            $directionId = $employeePosition->direction ? $employeePosition->direction->id : null;

            // Construir la consulta inicial para empleados disponibles
            $query = Employee::query()->where('id', '!=', $employeeId);

            // Filtrar por unidad o dirección del empleado solicitante
            if ($unitId) {
                $query->whereHas('position', function ($q) use ($unitId) {
                    $q->where('unit_id', $unitId);
                });
            } elseif ($directionId) {
                $query->whereHas('position', function ($q) use ($directionId) {
                    $q->where('direction_id', $directionId);
                });
            }

            // Excluir empleados con permisos conflictivos (solo permisos por días)
            $query->whereDoesntHave('leaves', function ($q) use ($startDate, $endDate) {
                $q->where(function ($query) use ($startDate, $endDate) {
                    // Considerar únicamente permisos por días
                    $query->whereNotNull('start_date')
                        ->whereNotNull('end_date')
                        ->where(function ($subQuery) use ($startDate, $endDate) {
                            $subQuery->whereBetween('start_date', [$startDate, $endDate])
                                ->orWhereBetween('end_date', [$startDate, $endDate])
                                ->orWhere(function ($subSubQuery) use ($startDate, $endDate) {
                                    $subSubQuery->where('start_date', '<=', $startDate)
                                        ->where('end_date', '>=', $endDate);
                                });
                        });
                })->whereHas('state', function ($query) {
                    $query->whereIn('name', ['Pendiente', 'Aprobado']);
                });
            });
            

            // Excluir empleados que ya son subrogados en el mismo rango de fechas
            $query->whereDoesntHave('delegations', function ($q) use ($startDate, $endDate) {
                $q->whereHas('leave', function ($leaveQuery) use ($startDate, $endDate) {
                    $leaveQuery->where(function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate])
                            ->orWhere(function ($subQuery) use ($startDate, $endDate) {
                                $subQuery->where('start_date', '<=', $startDate)
                                    ->where('end_date', '>=', $endDate);
                            });
                    });
                })->whereIn('status', ['Pendiente', 'Activa']);
            });
            

            // Obtener empleados disponibles con relaciones necesarias
            $availableEmployees = $query->with(['position.unit', 'position.direction'])->get();

            // Preparar los datos formateados
            $formattedEmployees = $availableEmployees->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'full_name' => $employee->getFullNameAttribute() ?: 'Sin nombre',
                    'short_name' => $employee->getNameAttribute() ?: 'Sin nombre',
                    'photo' => $employee->userPhoto(),
                    'position' => [
                        'id' => $employee->position->id ?? null,
                        'name' => $employee->position->name ?? 'Sin posición',
                        'unit' => $employee->position->unit->name ?? 'Sin unidad'
                    ]
                ];
            });

            // Verificar si hay empleados disponibles
            if ($formattedEmployees->isEmpty()) {
                return $this->successResponse('No hay candidatos disponibles para la subrogación en este rango de fechas.', []);
            }

            return $this->successResponse('Lista de empleados disponibles obtenida con éxito.', $formattedEmployees);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener empleados disponibles: ' . $e->getMessage(), 500);
        }
    }


    public function getSubrogationsByEmployee(int $employeeId): JsonResponse
    {
        try {
            // Obtener las subrogaciones donde el empleado es el delegado
            $subrogations = Delegation::with([
                'leave' => function ($query) {
                    $query->select('id', 'employee_id', 'start_date', 'end_date', 'reason', 'state_id')
                        ->with([
                            'employee:id,identification,first_name,second_name,last_name,second_last_name',
                            'state:id,name',
                        ]);
                },
                'responsibilities:id,name',
            ])->where('delegate_id', $employeeId)
                ->where('status', 'Activa')
                ->get();

            // Formatear la respuesta
            $formatted = $subrogations->map(function ($subrogation) {
                return [
                    'id' => $subrogation->id,
                    'status' => $subrogation->status,
                    // Información del delegado (responsable)
                    'delegated_to' => [
                        'id' => $subrogation->delegate_id,
                        'responsibilities' => $subrogation->responsibilities->map(function ($responsibility) {
                            return [
                                'id' => $responsibility->id,
                                'name' => $responsibility->name,
                            ];
                        }),
                    ],
                    // Información del permiso original que motivó la subrogación
                    'original_leave' => [
                        'id' => $subrogation->leave->id,
                        'start_date' => $subrogation->leave->start_date,
                        'end_date' => $subrogation->leave->end_date,
                        'reason' => $subrogation->leave->reason,
                        'state' => $subrogation->leave->state->name,
                        'requested_by' => [
                            'id' => $subrogation->leave->employee->id,
                            'identification' => $subrogation->leave->employee->identification,
                            'full_name' => $subrogation->leave->employee->getFullNameAttribute(),
                        ],
                    ],
                ];
            });


            return $this->successResponse('Subrogaciones obtenidas con éxito.', $formatted);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener las subrogaciones: ' . $e->getMessage(), 500);
        }
    }

// Obtener todas las subrogaciones
public function getAllSubrogations(): JsonResponse
{
    try {
        // Obtener todas las subrogaciones con sus relaciones necesarias
        $subrogations = Delegation::with([
            'leave' => function ($query) {
                $query->select('id', 'employee_id', 'start_date', 'end_date', 'reason', 'state_id')
                    ->with([
                        'employee:id,identification,first_name,second_name,last_name,second_last_name',
                        'state:id,name',
                    ]);
            },
            'responsibilities:id,name', // Relación de responsabilidades
            'delegate.position.unit.direction', // Relación con la unidad y su dirección
            'delegate.position.direction', // Relación directa con la dirección del cargo
        ])->get();

        // Formatear la respuesta
        $formatted = $subrogations->map(function ($subrogation) {
            return [
                'id' => $subrogation->id,
                'status' => $subrogation->status,
                'delegated_to' => [
                    'id' => $subrogation->delegate_id,
                    'full_name' => $subrogation->delegate->getFullNameAttribute() ?: 'Sin nombre',
                    'identification' => $subrogation->delegate->identification ?? 'No especificado',
                    'position' => [
                        'id' => $subrogation->delegate->position->id ?? null,
                        'name' => $subrogation->delegate->position->name ?? 'Sin posición',
                        'unit' => $subrogation->delegate->position->unit->name ?? null, // Nombre de la unidad, si aplica
                        'direction' => $subrogation->delegate->position->unit->direction->name // Dirección asociada a la unidad
                            ?? $subrogation->delegate->position->direction->name // Dirección directamente del cargo
                            ?? null, // Ninguna dirección
                    ],
                    'responsibilities' => $subrogation->responsibilities->map(function ($responsibility) {
                        return [
                            'id' => $responsibility->id,
                            'name' => $responsibility->name,
                        ];
                    }),
                ],
                'original_leave' => [
                    'id' => $subrogation->leave->id,
                    'start_date' => $subrogation->leave->start_date,
                    'end_date' => $subrogation->leave->end_date,
                    'reason' => $subrogation->leave->reason,
                    'state' => $subrogation->leave->state->name,
                    'requested_by' => [
                        'id' => $subrogation->leave->employee->id,
                        'identification' => $subrogation->leave->employee->identification,
                        'full_name' => $subrogation->leave->employee->getFullNameAttribute(),
                    ],
                ],
            ];
        });

        return $this->successResponse('Historial de subrogaciones obtenido con éxito.', $formatted);
    } catch (\Exception $e) {
        return $this->errorResponse('Error al obtener el historial de subrogaciones: ' . $e->getMessage(), 500);
    }
}



}
