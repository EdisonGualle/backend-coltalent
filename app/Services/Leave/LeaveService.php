<?php

namespace App\Services\Leave;

use App\Models\Leave\Leave;
use App\Models\Leave\LeaveComment;
use App\Models\Leave\LeaveState;
use App\Models\Employee\Employee;
use App\Models\Organization\Position;
use App\Models\Organization\Unit;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LeaveService extends ResponseService
{
    // Crear una solicitud de permiso para un empleado
    public function createLeave(int $employee_id, array $data): JsonResponse
    {
        DB::beginTransaction();

        try {
            // Establecer el estado inicial de la solicitud de permiso
            $data['employee_id'] = $employee_id;
            $data['state_id'] = LeaveState::where('name', 'Pendiente')->first()->id;

            // Crear la solicitud de permiso
            $leave = Leave::create($data);

            // Obtener el empleado que solicita el permiso
            $employee = Employee::findOrFail($employee_id);

            // Verificar si el empleado es un jefe general
            if ($employee->position->is_general_manager) {
                // Obtener el administrador (jefe de talento humano)
                $admin = Employee::whereHas('user', function ($query) {
                    $query->whereHas('role', function ($q) {
                        $q->where('name', 'Administrador');
                    });
                })->first();

                if (!$admin) {
                    throw new \Exception("No se encontró el jefe de talento humano");
                }

                // Crear el comentario inicial asignado al administrador
                LeaveComment::create([
                    'leave_id' => $leave->id,
                    'commented_by' => $admin->id,
                    'action' => 'Pendiente'
                ]);
            }
            // Verificar si el empleado es un jefe de dirección
            elseif ($employee->position->is_manager && $employee->position->direction_id) {
                // Si es jefe de dirección, enrutar al jefe general
                $generalManager = Position::where('is_general_manager', true)->first();

                if (!$generalManager || !$generalManager->employee) {
                    throw new \Exception("No se encontró el jefe general");
                }

                // Crear el comentario inicial asignado al jefe general
                LeaveComment::create([
                    'leave_id' => $leave->id,
                    'commented_by' => $generalManager->employee->id,
                    'action' => 'Pendiente'
                ]);
            } else {
                // Para otros empleados, determinar la dirección
                $directionId = $employee->position->unit_id ?
                    Unit::findOrFail($employee->position->unit_id)->direction_id :
                    $employee->position->direction_id;

                // Obtener el jefe de dirección
                $manager = Position::where('direction_id', $directionId)
                    ->where('is_manager', true)
                    ->first();

                if (!$manager || !$manager->employee) {
                    throw new \Exception("No se encontró el jefe de dirección");
                }

                // Crear el comentario inicial asignado al jefe de dirección
                LeaveComment::create([
                    'leave_id' => $leave->id,
                    'commented_by' => $manager->employee->id,
                    'action' => 'Pendiente'
                ]);
            }

            DB::commit();

            return $this->successResponse('Solicitud de permiso creada con éxito', $leave, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('No se pudo crear la solicitud de permiso: ' . $e->getMessage(), 500);
        }
    }

    public function updateLeave(int $employee_id, int $leave_id, array $data): JsonResponse
    {
        DB::beginTransaction();

        try {
            $leave = Leave::findOrFail($leave_id);

            // Validar que la solicitud está en estado "Corregir"
            if ($leave->state->name !== 'Corregir') {
                throw new \Exception("No se puede editar una solicitud de permiso que no está en estado 'Corregir'");
            }

            // Validar que la solicitud es del empleado correcto
            if ($leave->employee_id !== $employee_id) {
                throw new \Exception("El empleado no está autorizado para editar esta solicitud de permiso");
            }

            // Actualizar la solicitud de permiso
            $leave->update($data);

            // Obtener el comentario más reciente
            $currentComment = $leave->comments()->orderBy('created_at', 'desc')->first();

            if ($currentComment) {
                // Obtener el aprobador actual
                $approver = Employee::find($currentComment->commented_by);

                // Crear un nuevo comentario asignado al mismo aprobador
                LeaveComment::create([
                    'leave_id' => $leave->id,
                    'commented_by' => $approver->id,
                    'action' => 'Pendiente',
                ]);
            }

            // Cambiar el estado de la solicitud a "Pendiente" después de corregirla
            $leave->update(['state_id' => LeaveState::where('name', 'Pendiente')->first()->id]);

            DB::commit();
            return $this->successResponse('Solicitud de permiso actualizada con éxito', $leave);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('No se pudo actualizar la solicitud de permiso: ' . $e->getMessage(), 500);
        }
    }



    // Obtener las solicitudes de permiso de un empleado asignado con filtros opcionales para el estado 
    public function getLeavesByFilter(int $employee_id, string $filter): JsonResponse
    {
        try {
            // Obtener el usuario actual y su rol
            $currentEmployee = Employee::with('user.role')->find($employee_id);
            if (!$currentEmployee) {
                throw new \Exception("Empleado no encontrado");
            }
            $currentUserRole = $currentEmployee->user->role->name;
            $currentEmployeeId = $currentEmployee->id;

            $query = Leave::query();

            switch ($filter) {
                case 'pendientes':
                    $query->whereHas('comments', function ($q) use ($currentEmployeeId) {
                        $q->where('commented_by', $currentEmployeeId)
                            ->where('action', 'Pendiente');
                    });
                    break;
                case 'aprobados':
                    $query->whereHas('comments', function ($q) use ($currentEmployeeId) {
                        $q->where('commented_by', $currentEmployeeId)
                            ->where('action', 'Aprobado');
                    });
                    break;
                case 'rechazados':
                    $query->whereHas('comments', function ($q) use ($currentEmployeeId) {
                        $q->where('commented_by', $currentEmployeeId)
                            ->where('action', 'Rechazado');
                    });
                    break;
                case 'Corregir':
                    $query->whereHas('comments', function ($q) use ($currentEmployeeId) {
                        $q->where('commented_by', $currentEmployeeId)
                            ->where('action', 'Corregir');
                    });
                    break;
                case 'todos':
                default:
                    $query->whereHas('comments', function ($q) use ($currentEmployeeId) {
                        $q->where('commented_by', $currentEmployeeId);
                    });
                    break;
            }

            $leaves = $query->with([
                'employee:id,identification,position_id,first_name,second_name,last_name,second_last_name',
                'employee.position:id,name,unit_id,direction_id',
                'employee.position.unit:id,name,direction_id',
                'employee.position.unit.direction:id,name',
                'employee.position.direction:id,name',
                'leaveType:id,name',
                'state:id,name',
                'comments.rejectionReason',
                'comments.commentedBy:id,position_id,first_name,second_name,last_name,second_last_name',
                'comments.commentedBy.position:id,name'
            ])->get();

            // Filtrar y formatear los comentarios
            $leaves->each(function ($leave) use ($currentUserRole, $currentEmployeeId) {
                $filteredComments = collect();

                // Filtrar comentarios basados en el rol del usuario
                if ($currentUserRole === 'Administrador') {
                    $filteredComments = $leave->comments;
                } else {
                    $filteredComments = $leave->comments->filter(function ($comment) use ($currentEmployeeId) {
                        return $comment->commented_by == $currentEmployeeId;
                    });
                }

                // Formatear los comentarios como comentario_uno, comentario_dos
                $filteredComments->values()->each(function ($comment, $index) use ($leave) {
                    $leave->{'comentario_' . ($index + 1)} = [
                        'comment' => $comment->comment,
                        'commented_by_full_name' => $comment->commentedBy ? $comment->commentedBy->getFullNameAttribute() : null,
                        'commented_by_name' => $comment->commentedBy ? $comment->commentedBy->getNameAttribute() : null,
                        'commented_by_position' => $comment->commentedBy && $comment->commentedBy->position ? $comment->commentedBy->position->name : null,
                        'rejection_reason' => $comment->rejectionReason ? $comment->rejectionReason->reason : null,
                        'action' => $comment->action ? $comment->action : null,
                        'created_at' => $comment->created_at,
                    ];
                });

                // Obtener la dirección de la unidad si existe, o directamente la dirección del puesto
                $direction = $leave->employee->position->unit
                    ? $leave->employee->position->unit->direction
                    : $leave->employee->position->direction;

                $unit = $leave->employee->position->unit;

                $organization_details = "Dirección: " . ($direction ? $direction->name : '');
                if ($unit) {
                    $organization_details .= "\nUnidad: " . $unit->name;
                }
                $leave->employee->organization_details = $organization_details;


                // Añadir full_name, name y position_name del empleado
                $leave->employee->full_name = $leave->employee->getFullNameAttribute();
                $leave->employee->name = $leave->employee->getNameAttribute();
                $leave->employee->position_name = $leave->employee->position ? $leave->employee->position->name : null;


                // Calcular la duración del permiso
                if ($leave->start_date && $leave->end_date) {
                    $start_date = new \DateTime($leave->start_date);
                    $end_date = new \DateTime($leave->end_date);
                    $interval = $start_date->diff($end_date);
                    $days = $interval->days + 1; // Incluye el último día
                    $leave->duration = $days . ' ' . ($days > 1 ? 'Días' : 'Día');
                    $leave->requested_period = $leave->start_date . "\n" . $leave->end_date;
                } elseif ($leave->start_time && $leave->end_time) {
                    $start_time = new \DateTime($leave->start_time);
                    $end_time = new \DateTime($leave->end_time);
                    $interval = $start_time->diff($end_time);
                    $hours = $interval->h;
                    $minutes = $interval->i;
                    if ($hours > 0) {
                        if ($minutes > 0) {
                            $leave->duration = $hours . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ' Horas';
                        } else {
                            $leave->duration = $hours . ' ' . ($hours > 1 ? 'Horas' : 'Hora');
                        }
                    } else {
                        $leave->duration = $minutes . ' Minutos';
                    }
                    $leave->requested_period = $leave->start_date;
                } else {
                    $leave->duration = null;
                    $leave->requested_period = $leave->start_date;
                }

                // Ocultar los campos innecesarios
                $leave->employee->makeHidden(['first_name', 'second_name', 'last_name', 'second_last_name', 'position']);
                $leave->makeHidden(['employee_id', 'leave_type_id', 'state_id', 'comments']);
            });

            return $this->successResponse('Solicitudes de permisos obtenidas con éxito', $leaves);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener las solicitudes de permisos: ' . $e->getMessage(), 500);
        }
    }


    public function getLeavesByEmployee(int $employee_id, string $filter): JsonResponse
    {
        try {
            $query = Leave::where('employee_id', $employee_id);

            switch ($filter) {
                case 'pendientes':
                    $query->whereHas('state', function ($q) {
                        $q->where('name', 'Pendiente');
                    });
                    break;
                case 'aprobados':
                    $query->whereHas('state', function ($q) {
                        $q->where('name', 'Aprobado');
                    });
                    break;
                case 'rechazados':
                    $query->whereHas('state', function ($q) {
                        $q->where('name', 'Rechazado');
                    });
                    break;
                case 'Corregir':
                    $query->whereHas('state', function ($q) {
                        $q->where('name', 'Corregir');
                    });
                    break;
                case 'todas':
                default:
                    // No filters, return all leaves for the employee
                    break;
            }

            $leaves = $query->with([
                'leaveType',
                'state',
                'comments',
                'comments.rejectionReason',
                'comments.commentedBy:id,position_id,first_name,second_name,last_name,second_last_name',
                'comments.commentedBy.position:id,name'
            ])->get();

            // Formatear los comentarios
            $leaves->each(function ($leave) {
                $leave->comments->each(function ($comment) {
                    $comment->commented_by_full_name = $comment->commentedBy ? $comment->commentedBy->getFullNameAttribute() : null;
                    $comment->commented_by_name = $comment->commentedBy ? $comment->commentedBy->getNameAttribute() : null;
                    $comment->commented_by_position = $comment->commentedBy && $comment->commentedBy->position ? $comment->commentedBy->position->name : null;
                    $comment->makeHidden(['commented_by', 'commentedBy']);
                });

                // Ocultar los campos innecesarios en Leave
                $leave->makeHidden(['leave_type_id', 'state_id']);
            });

            return $this->successResponse('Solicitudes de permisos obtenidas con éxito', $leaves);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener las solicitudes de permisos: ' . $e->getMessage(), 500);
        }
    }
}

