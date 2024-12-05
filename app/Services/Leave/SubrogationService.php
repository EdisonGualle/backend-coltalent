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

    // Obtener las subrogaciones de un empleado en específico (delegado)
    public function getSubrogationsByEmployee(int $employeeId): JsonResponse
    {
        try {
            $subrogations = Delegation::with([
                'leave' => function ($query) {
                    $query->select('id', 'employee_id', 'start_date', 'end_date', 'reason', 'state_id')
                        ->with([
                            'employee:id,identification,first_name,second_name,last_name,second_last_name',
                            'state:id,name',
                        ]);
                },
                'responsibilities:id,name',
                'leave.comments' => function ($query) {
                    $query->orderBy('created_at', 'asc')->limit(1)
                        ->with('commentedBy:id,first_name,second_name,last_name,second_last_name,identification');
                }
            ])->where('delegate_id', $employeeId)
                ->where('status', 'Activa')
                ->get();

            $formatted = $subrogations->map(function ($subrogation) {
                return [
                    'id' => $subrogation->id,
                    'status' => $subrogation->status,
                    'reason' => $subrogation->reason,
                    'delegated_by' => [
                        'id' => $subrogation->leave->comments->first()?->commentedBy->id ?? null,
                        'full_name' => trim(implode(' ', [
                            $subrogation->leave->comments->first()?->commentedBy->first_name ?? '',
                            $subrogation->leave->comments->first()?->commentedBy->second_name ?? '',
                            $subrogation->leave->comments->first()?->commentedBy->last_name ?? '',
                            $subrogation->leave->comments->first()?->commentedBy->second_last_name ?? '',
                        ])) ?: 'N/A',
                        'identification' => $subrogation->leave->comments->first()?->commentedBy->identification ?? 'N/A',
                        'decision_date' => $subrogation->leave->comments->first()?->updated_at ?? null, // Fecha de decisión
                    ],
                    'responsibilities' => $subrogation->responsibilities->map(function ($responsibility) {
                        return [
                            'id' => $responsibility->id,
                            'name' => $responsibility->name,
                        ];
                    }),
                    'original_leave' => [
                        'id' => $subrogation->leave->id,
                        'start_date' => $subrogation->leave->start_date,
                        'end_date' => $subrogation->leave->end_date,
                        'reason' => $subrogation->leave->reason,
                        'state' => $subrogation->leave->state->name,
                        'requested_by' => [
                            'id' => $subrogation->leave->employee->id,
                            'identification' => $subrogation->leave->employee->identification,
                            'full_name' => trim(implode(' ', [
                                $subrogation->leave->employee->first_name ?? '',
                                $subrogation->leave->employee->second_name ?? '',
                                $subrogation->leave->employee->last_name ?? '',
                                $subrogation->leave->employee->second_last_name ?? '',
                            ])) ?: 'N/A',
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
            $subrogations = Delegation::with([
                'leave' => function ($query) {
                    $query->select('id', 'employee_id', 'start_date', 'end_date', 'reason', 'state_id')
                        ->with([
                            'employee:id,identification,first_name,second_name,last_name,second_last_name',
                            'state:id,name',
                        ]);
                },
                'delegate:id,first_name,second_name,last_name,second_last_name,identification,position_id',
                'delegate.position:id,name,unit_id,direction_id',
                'delegate.position.unit:id,name,direction_id',
                'delegate.position.unit.direction:id,name',
                'responsibilities:id,name',
                'leave.comments' => function ($query) {
                    $query->orderBy('created_at', 'asc')->limit(1)
                        ->with('commentedBy:id,first_name,second_name,last_name,second_last_name,identification');
                }
            ])->get();

            // Formatear la respuesta
            $formatted = $subrogations->map(function ($subrogation) {
                return [
                    'id' => $subrogation->id,
                    'status' => $subrogation->status,
                    'reason' => $subrogation->reason, 
                    'delegated_by' => [ // Información de quién asignó la delegación
                        'id' => $subrogation->leave->comments->first()?->commentedBy->id ?? null,
                        'full_name' => trim(implode(' ', [
                            $subrogation->leave->comments->first()?->commentedBy->first_name ?? '',
                            $subrogation->leave->comments->first()?->commentedBy->second_name ?? '',
                            $subrogation->leave->comments->first()?->commentedBy->last_name ?? '',
                            $subrogation->leave->comments->first()?->commentedBy->second_last_name ?? '',
                        ])) ?: 'N/A',
                        'identification' => $subrogation->leave->comments->first()?->commentedBy->identification ?? 'N/A',
                        'decision_date' => $subrogation->leave->comments->first()?->updated_at ?? null, // Fecha de decisión
                    ],
                    'delegated_to' => [
                        'id' => $subrogation->delegate->id,
                        'full_name' => trim(implode(' ', [
                            $subrogation->delegate->first_name ?? '',
                            $subrogation->delegate->second_name ?? '',
                            $subrogation->delegate->last_name ?? '',
                            $subrogation->delegate->second_last_name ?? '',
                        ])) ?: 'N/A',
                        'identification' => $subrogation->delegate->identification,
                        'position' => [
                            'id' => $subrogation->delegate->position->id,
                            'name' => $subrogation->delegate->position->name,
                            'unit' => $subrogation->delegate->position->unit->name,
                            'direction' => $subrogation->delegate->position->unit->direction->name,
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
                            'full_name' => trim(implode(' ', [
                                $subrogation->leave->employee->first_name ?? '',
                                $subrogation->leave->employee->second_name ?? '',
                                $subrogation->leave->employee->last_name ?? '',
                                $subrogation->leave->employee->second_last_name ?? '',
                            ])) ?: 'N/A',
                        ],
                    ],
                ];
            });

            return $this->successResponse('Historial de subrogaciones obtenido con éxito.', $formatted);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener el historial de subrogaciones: ' . $e->getMessage(), 500);
        }
    }

    // Obtener las delegaciones asignadas por jefe inmediato
    public function getDelegationsAssignedByEmployee(int $employeeId): JsonResponse
    {
        try {
            $delegations = Delegation::with([
                'leave' => function ($query) {
                    $query->select('id', 'employee_id', 'start_date', 'end_date', 'reason', 'state_id')
                        ->with([
                            'employee:id,identification,first_name,second_name,last_name,second_last_name',
                            'state:id,name',
                            'comments' => function ($query) {
                                $query->orderBy('created_at', 'asc')->limit(1)
                                    ->with('commentedBy:id,identification,first_name,second_name,last_name,second_last_name');
                            },
                        ]);
                },
                'delegate:id,identification,first_name,second_name,last_name,second_last_name,position_id', // Quien recibe la delegación
                'delegate.position:id,name,unit_id,direction_id',
                'delegate.position.unit:id,name,direction_id',
                'delegate.position.unit.direction:id,name',
                'responsibilities:id,name',
            ])->whereHas('leave.comments', function ($query) use ($employeeId) {
                $query->where('commented_by', $employeeId); // Filtrar por el empleado que asignó la delegación
            })->get();

            // Formatear la respuesta
            $formatted = $delegations->map(function ($delegation) {
                $firstComment = $delegation->leave->comments->first(); // Primer comentario relacionado
                return [
                    'id' => $delegation->id,
                    'status' => $delegation->status,
                    'reason' => $delegation->reason,
                    'decision_date' => $firstComment?->updated_at ?? null, // Fecha de decisión del comentario
                    'delegated_to' => [ // Información del empleado delegado
                        'id' => $delegation->delegate->id,
                        'full_name' => trim(implode(' ', [
                            $delegation->delegate->first_name ?? '',
                            $delegation->delegate->second_name ?? '',
                            $delegation->delegate->last_name ?? '',
                            $delegation->delegate->second_last_name ?? '',
                        ])) ?: 'N/A',
                        'identification' => $delegation->delegate->identification ?? 'N/A',
                        'position' => [
                            'id' => $delegation->delegate->position->id ?? null,
                            'name' => $delegation->delegate->position->name ?? 'N/A',
                            'unit' => $delegation->delegate->position->unit->name ?? 'N/A',
                            'direction' => $delegation->delegate->position->unit->direction->name ?? 'N/A',
                        ],
                        'responsibilities' => $delegation->responsibilities->map(function ($responsibility) {
                            return [
                                'id' => $responsibility->id,
                                'name' => $responsibility->name,
                            ];
                        }),
                    ],
                    'original_leave' => [
                        'id' => $delegation->leave->id,
                        'reason' => $delegation->leave->reason,
                        'start_date' => $delegation->leave->start_date,
                        'end_date' => $delegation->leave->end_date,
                        'state' => $delegation->leave->state->name,
                        'requested_by' => [
                            'id' => $delegation->leave->employee->id,
                            'identification' => $delegation->leave->employee->identification ?? 'N/A',
                            'full_name' => trim(implode(' ', [
                                $delegation->leave->employee->first_name ?? '',
                                $delegation->leave->employee->second_name ?? '',
                                $delegation->leave->employee->last_name ?? '',
                                $delegation->leave->employee->second_last_name ?? '',
                            ])) ?: 'N/A',
                        ],
                    ],
                ];
            });

            return $this->successResponse('Delegaciones asignadas por el empleado obtenidas con éxito.', $formatted);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener delegaciones: ' . $e->getMessage(), 500);
        }
    }

}
