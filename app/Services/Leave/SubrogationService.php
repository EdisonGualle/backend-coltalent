<?php

namespace App\Services\Leave;

use App\Models\Employee\Employee;
use App\Models\Leave\Delegation;
use App\Models\Leave\Leave;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
            $query = Employee::query()
                ->where('id', '!=', $employeeId)
                ->whereHas('currentContract');

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

            // Obtener todas las delegaciones asociadas al empleado
            $subrogations = Delegation::with([
                'leave' => function ($query) {
                    $query->select('id', 'employee_id', 'start_date', 'end_date', 'reason', 'state_id')
                        ->with([
                            'employee:id,identification,first_name,second_name,last_name,second_last_name,position_id',
                            'employee.position:id,name',
                            'state:id,name',
                            'comments' => function ($query) {
                                $query->orderBy('created_at', 'asc');
                            },
                        ]);
                },
                'responsibilities:id,name',
            ])->where('delegate_id', $employeeId)
                ->where('status', 'Activa')
                ->get();

            // Formatear las subrogaciones
            $formatted = $subrogations->map(function ($subrogation) {
                $delegationStatus = $this->calculateDelegationStatus(
                    $subrogation->leave?->start_date,
                    $subrogation->leave?->end_date
                );

                // Obtener todos los comentarios del permiso asociado
                $allComments = $subrogation->leave?->comments ?? collect();

                // Tomar siempre el primer comentario si existe
                $firstComment = $allComments->first();
                $commentedBy = $firstComment?->commentedBy;

                $delegatedBy = $commentedBy ? [
                    'id' => $commentedBy->id,
                    'full_name' => trim(implode(' ', [
                        $commentedBy->first_name ?? '',
                        $commentedBy->second_name ?? '',
                        $commentedBy->last_name ?? '',
                        $commentedBy->second_last_name ?? '',
                    ])) ?: 'N/A',
                    'identification' => $commentedBy->identification ?? 'N/A',
                    'decision_date' => $firstComment?->updated_at ?? null,
                ] : null;

                return [
                    'id' => $subrogation->id,
                    'status' => $delegationStatus,
                    'reason' => $subrogation->reason,
                    'delegated_by' => $delegatedBy,
                    'responsibilities' => $subrogation->responsibilities->map(function ($responsibility) {
                        return [
                            'id' => $responsibility->id,
                            'name' => $responsibility->name,
                        ];
                    }),
                    'original_leave' => [
                        'id' => $subrogation->leave?->id ?? null,
                        'start_date' => $subrogation->leave?->start_date
                            ? Carbon::parse($subrogation->leave->start_date)->format('d/m/Y')
                            : null,
                        'end_date' => $subrogation->leave?->end_date
                            ? Carbon::parse($subrogation->leave->end_date)->format('d/m/Y')
                            : null,
                        'reason' => $subrogation->leave?->reason ?? 'N/A',
                        'state' => $subrogation->leave?->state?->name ?? 'N/A',
                        'requested_by' => [
                            'id' => $subrogation->leave?->employee->id ?? null,
                            'identification' => $subrogation->leave?->employee->identification ?? 'N/A',
                            'full_name' => trim(implode(' ', [
                                $subrogation->leave?->employee?->first_name ?? '',
                                $subrogation->leave?->employee?->second_name ?? '',
                                $subrogation->leave?->employee?->last_name ?? '',
                                $subrogation->leave?->employee?->second_last_name ?? '',
                            ])) ?: 'N/A',
                            'position' => $subrogation->leave?->employee?->position?->name ?? 'No especificado',
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
            // Obtener solo las delegaciones con estado "Activa"
            $subrogations = Delegation::with([
                'leave' => function ($query) {
                    $query->select('id', 'employee_id', 'start_date', 'end_date', 'reason', 'state_id')
                        ->with([
                            'employee:id,identification,first_name,second_name,last_name,second_last_name,position_id',
                            'employee.position:id,name,unit_id,direction_id',
                            'state:id,name',
                            'comments' => function ($query) {
                                $query->orderBy('created_at', 'asc')->with(
                                    'commentedBy:id,first_name,second_name,last_name,second_last_name,identification,position_id',
                                    'commentedBy.position:id,name,unit_id,direction_id'
                                );
                            },
                        ]);
                },
                'delegate:id,first_name,second_name,last_name,second_last_name,identification,position_id',
                'delegate.position:id,name,unit_id,direction_id',
                'delegate.position.unit:id,name,direction_id',
                'delegate.position.unit.direction:id,name',
                'responsibilities:id,name',
            ])
                ->where('status', 'Activa') // Filtrar delegaciones activas
                ->get();

            $formatted = $subrogations->map(function ($subrogation) {
                // Obtener todos los comentarios asociados al permiso
                $allComments = $subrogation->leave->comments;

                // Obtener el primer comentario (delegador)
                $firstComment = $allComments->first();

                // Validar el autor del comentario
                $commentedBy = $firstComment?->commentedBy;
                if ($commentedBy) {
                    $fullName = trim(implode(' ', [
                        $commentedBy->first_name ?? '',
                        $commentedBy->second_name ?? '',
                        $commentedBy->last_name ?? '',
                        $commentedBy->second_last_name ?? '',
                    ])) ?: 'N/A';
                    $position = [
                        'id' => $commentedBy->position?->id ?? null,
                        'name' => $commentedBy->position?->name ?? 'No especificado',
                        'unit' => $commentedBy->position?->unit?->name ?? 'No especificado',
                        'direction' => $commentedBy->position?->unit?->direction?->name ?? 'No especificado',
                    ];
                } else {
                    $fullName = 'N/A';
                    $position = null;
                }

                // Calcular el estado de la delegación
                $status = $this->calculateDelegationStatus($subrogation->leave->start_date, $subrogation->leave->end_date);

                return [
                    'id' => $subrogation->id,
                    'status' => $status,
                    'reason' => $subrogation->reason,
                    'delegated_by' => [
                        'id' => $commentedBy?->id ?? null,
                        'full_name' => $fullName,
                        'identification' => $commentedBy?->identification ?? 'N/A',
                        'decision_date' => $firstComment?->updated_at ?? null,
                        'position' => $position,
                    ],
                    'delegated_to' => [
                        'id' => $subrogation->delegate->id,
                        'full_name' => trim(implode(' ', [
                            $subrogation->delegate->first_name ?? '',
                            $subrogation->delegate->second_name ?? '',
                            $subrogation->delegate->last_name ?? '',
                            $subrogation->delegate->second_last_name ?? '',
                        ])) ?: 'N/A',
                        'identification' => $subrogation->delegate->identification ?? 'N/A',
                        'position' => [
                            'id' => $subrogation->delegate->position?->id ?? null,
                            'name' => $subrogation->delegate->position?->name ?? 'No especificado',
                            'unit' => $subrogation->delegate->position?->unit?->name ?? 'No especificado',
                            'direction' => $subrogation->delegate->position?->unit?->direction?->name ?? 'No especificado',
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
                        'start_date' => Carbon::parse($subrogation->leave->start_date)->format('d/m/Y'),
                        'end_date' => Carbon::parse($subrogation->leave->end_date)->format('d/m/Y'),
                        'reason' => $subrogation->leave->reason,
                        'state' => $subrogation->leave->state->name,
                        'requested_by' => [
                            'id' => $subrogation->leave->employee->id,
                            'identification' => $subrogation->leave->employee->identification ?? 'N/A',
                            'full_name' => trim(implode(' ', [
                                $subrogation->leave->employee->first_name ?? '',
                                $subrogation->leave->employee->second_name ?? '',
                                $subrogation->leave->employee->last_name ?? '',
                                $subrogation->leave->employee->second_last_name ?? '',
                            ])) ?: 'N/A',
                            'position' => [
                                'id' => $subrogation->leave->employee->position?->id ?? null,
                                'name' => $subrogation->leave->employee->position?->name ?? 'No especificado',
                                'unit' => $subrogation->leave->employee->position?->unit?->name ?? 'No especificado',
                                'direction' => $subrogation->leave->employee->position?->unit?->direction?->name ?? 'No especificado',
                            ],
                        ],
                    ],
                ];
            });

            return $this->successResponse('Historial de subrogaciones obtenido con éxito.', $formatted);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener subrogaciones: ' . $e->getMessage(), 500);
        }
    }

    // Obtener las delegaciones asignadas por jefe inmediato
    public function getDelegationsAssignedByEmployee(int $employeeId): JsonResponse
    {
        try {
            // Filtrar delegaciones con estado "Activa" antes de realizar cálculos
            $delegations = Delegation::with([
                'leave' => function ($query) {
                    $query->select('id', 'employee_id', 'start_date', 'end_date', 'reason', 'state_id')
                        ->with([
                            'employee:id,identification,first_name,second_name,last_name,second_last_name,position_id',
                            'employee.position:id,name,unit_id,direction_id',
                            'employee.position.unit:id,name,direction_id',
                            'employee.position.unit.direction:id,name',
                            'employee.position.direction:id,name',
                            'state:id,name',
                            'comments' => function ($query) {
                                $query->orderBy('created_at', 'asc')->with('commentedBy:id,identification,first_name,second_name,last_name,second_last_name');
                            },
                        ]);
                },
                'delegate:id,identification,first_name,second_name,last_name,second_last_name,position_id',
                'delegate.position:id,name,unit_id,direction_id',
                'delegate.position.unit:id,name,direction_id',
                'delegate.position.unit.direction:id,name',
                'delegate.position.direction:id,name',
                'responsibilities:id,name',
            ])
                ->where('status', 'Activa') // Filtrar delegaciones activas
                ->whereHas('leave.comments', function ($query) use ($employeeId) {
                    $query->where('commented_by', $employeeId);
                })
                ->get();


            $formatted = $delegations->filter(function ($delegation) use ($employeeId) {
                $firstComment = $delegation->leave->comments->first();

                // Verificar si existe un primer comentario y si el comentario es realizado por el empleado indicado
                if (!$firstComment || $firstComment->commentedBy->id !== $employeeId) {
                    return false; // Excluir delegaciones donde el primer comentario no coincide
                }

                return true; // Incluir delegaciones válidas
            })->map(function ($delegation) {
                // Obtener el primer comentario
                $firstComment = $delegation->leave->comments->first();

                $status = $this->calculateDelegationStatus($delegation->leave->start_date, $delegation->leave->end_date);

                return [
                    'id' => $delegation->id,
                    'status' => $status,
                    'reason' => $delegation->reason,
                    'decision_date' => $firstComment?->updated_at ?? null,
                    'delegated_to' => [
                        'id' => $delegation->delegate->id,
                        'full_name' => trim(implode(' ', [
                            $delegation->delegate->first_name ?? '',
                            $delegation->delegate->second_name ?? '',
                            $delegation->delegate->last_name ?? '',
                            $delegation->delegate->second_last_name ?? '',
                        ])) ?: 'N/A',
                        'identification' => $delegation->delegate->identification ?? 'N/A',
                        'position' => [
                            'id' => $delegation->delegate->position?->id,
                            'name' => $delegation->delegate->position?->name ?? 'N/A',
                            'unit' => $delegation->delegate->position?->unit?->name ?? 'N/A',
                            'direction' => $delegation->delegate->position?->direction?->name ?? $delegation->delegate->position?->unit?->direction?->name ?? 'No especificado',
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
                        'start_date' => Carbon::parse($delegation->leave->start_date)->format('d/m/Y'),
                        'end_date' => Carbon::parse($delegation->leave->end_date)->format('d/m/Y'),
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
                            'position' => [
                                'id' => $delegation->leave->employee->position?->id,
                                'name' => $delegation->leave->employee->position?->name ?? 'No especificado',
                                'unit' => $delegation->leave->employee->position?->unit?->name ?? 'No especificado',
                                'direction' => $delegation->leave->employee->position?->direction?->name ?? $delegation->leave->employee->position?->unit?->direction?->name ?? 'No especificado',
                            ],
                        ],
                    ],
                ];
            });

            return $this->successResponse('Delegaciones asignadas por el empleado obtenidas con éxito.', $formatted);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener delegaciones: ' . $e->getMessage(), 500);
        }
    }

    private function calculateDelegationStatus(string $startDate, string $endDate): string
    {
        $currentDate = Carbon::now()->toDateString();
        $start = Carbon::parse($startDate)->toDateString();
        $end = Carbon::parse($endDate)->toDateString();

        return match (true) {
            $currentDate < $start => 'En espera',
            $currentDate >= $start && $currentDate <= $end => 'En curso',
            $currentDate > $end => 'Finalizada',
            default => 'Estado desconocido',
        };
    }

}
