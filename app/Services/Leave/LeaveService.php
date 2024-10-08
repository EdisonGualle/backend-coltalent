<?php

namespace App\Services\Leave;

use App\Events\NotificationEvent;
use App\Models\Leave\Leave;
use App\Models\Leave\LeaveComment;
use App\Models\Leave\LeaveState;
use App\Models\Employee\Employee;
use App\Models\Leave\LeaveType;
use App\Models\Notification;
use App\Models\Organization\Position;
use App\Models\Organization\Unit;
use App\Models\User;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Mail\LeaveRequestedMail;
use Illuminate\Support\Facades\Mail;
use App\Mail\ApprovalNotificationMail;

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

            // Variable para almacenar el ID del aprobador
            $approverId = null;

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
                $approverId = $admin->id;
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

                $approverId = $generalManager->employee->id;
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

                $approverId = $manager->employee->id;
                // Crear el comentario inicial asignado al jefe de dirección
                LeaveComment::create([
                    'leave_id' => $leave->id,
                    'commented_by' => $manager->employee->id,
                    'action' => 'Pendiente'
                ]);
            }

            // Crear la notificación para el primer aprobador
            $this->createPendingNotification($leave, $approverId);

            // Enviar correo al empleado solicitante
            $approver = Employee::findOrFail($approverId);
            Mail::to($employee->user->email)->send(new LeaveRequestedMail($employee, $leave, $approver));


            DB::commit();

            return $this->successResponse('Solicitud de permiso creada con éxito', $leave, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('No se pudo crear la solicitud de permiso: ' . $e->getMessage(), 500);
        }
    }

    // Crear una notificación para el próximo aprobador
    private function createPendingNotification(Leave $leave, int $approver_id)
    {
        $approver = Employee::find($approver_id);
        $approverName = $approver ? $approver->full_name : 'Aprobador';
        $approverPhoto = $approver ? $approver->user->photo : null;
        $applicantPhoto = $leave->employee && $leave->employee->user && $leave->employee->user->photo ? $leave->employee->user->photo : null;
        $startDate = Carbon::parse($leave->start_date)->format('d-m-Y');
        $applicantName = $leave->employee->full_name; // Asegúrate de tener la relación employee definida en el modelo Leave

        $message = "Tienes una nueva solicitud de permiso de {$applicantName} para el día {$startDate}, pendiente de aprobación.";

        // Obtener el usuario asociado al empleado que es el próximo aprobador
        $user = User::where('employee_id', $approver_id)->first();

        if (!$user) {
            throw new \Exception("No se encontró el usuario asociado al empleado con ID: {$approver_id}");
        }

        $notification = Notification::create([
            'user_id' => $user->id,  // Usuario que recibe la notificación
            'type' => 'Solicitud pendiente',
            'data' => [
                'leave_id' => $leave->id,
                'message' => $message,
                'approver_id' => $approver_id,
                'applicant_photo' => $applicantPhoto,
            ],
        ]);

        event(new NotificationEvent($notification));

        // Enviar el correo electrónico al aprobador
    Mail::to($user->email)->send(new ApprovalNotificationMail($approver, $leave, $leave->employee));
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
                case 'historial':
                    $query->whereHas('comments', function ($q) use ($currentEmployeeId) {
                        $q->where('commented_by', $currentEmployeeId);
                    });
                    break;
                default:
                    break;
            }
            // Ordenar los resultados por el id en orden descendente
            $query->orderBy('id', 'desc');

            $leaves = $query->with([
                'employee:id,identification,position_id,first_name,second_name,last_name,second_last_name',
                'employee.position:id,name,unit_id,direction_id',
                'employee.position.unit:id,name,direction_id',
                'employee.position.unit.direction:id,name',
                'employee.position.direction:id,name',
                'leaveType' => function ($query) {
                    $query->withTrashed()->select('id', 'name');
                },
                'state:id,name',
                'comments' => function ($query) {
                    $query->with([
                        'rejectionReason' => function ($query) {
                            $query->withTrashed();
                        }
                    ]);
                },
                'comments.commentedBy:id,position_id,first_name,second_name,last_name,second_last_name',
                'comments.commentedBy.position:id,name'
            ])->get();

            // Formatear y añadir los comentarios al permiso
            $leaves->each(function ($leave) use ($currentUserRole) {
                $comments = $leave->comments;

                // Asignar comentarios
                $leave->comentario_1 = $comments->first(function ($comment) {
                    return $comment->commentedBy->user->role->name !== 'Administrador';
                });

                $leave->comentario_2 = $comments->first(function ($comment) {
                    return $comment->commentedBy->user->role->name === 'Administrador';
                });

                // Formatear los comentarios
                foreach (['comentario_1', 'comentario_2'] as $commentKey) {
                    if ($leave->{$commentKey}) {
                        $comment = $leave->{$commentKey};
                        $interactionDate = $comment->created_at != $comment->updated_at ? $comment->updated_at : null;
                        $leave->{$commentKey} = [
                            'id' => $comment->id,
                            'comment' => $comment->comment,
                            'commented_by_full_name' => $comment->commentedBy ? $comment->commentedBy->getFullNameAttribute() : null,
                            'commented_by_name' => $comment->commentedBy ? $comment->commentedBy->getNameAttribute() : null,
                            'commented_by_position' => $comment->commentedBy && $comment->commentedBy->position ? $comment->commentedBy->position->name : null,
                            'rejection_reason' => $comment->rejectionReason ? $comment->rejectionReason->reason : null,
                            'action' => $comment->action ? $comment->action : null,
                            'interaction_date' => $interactionDate,
                        ];
                    }
                }

                // Obtener la dirección de la unidad si existe, o directamente la dirección del puesto
                $direction = $leave->employee->position->unit
                    ? $leave->employee->position->unit->direction
                    : $leave->employee->position->direction;

                $unit = $leave->employee->position->unit;

                $leave->employee->direction_name = $direction ? $direction->name : null;
                $leave->employee->unit_name = $unit ? $unit->name : null;

                // Añadir full_name, name y position_name del empleado
                $leave->employee->full_name = $leave->employee->getFullNameAttribute();
                $leave->employee->name = $leave->employee->getNameAttribute();
                $leave->employee->position_name = $leave->employee->position ? $leave->employee->position->name : null;

                // Calcular la duración del permiso
                if ($leave->start_date && $leave->end_date) {
                    $start_date = \DateTime::createFromFormat('Y-m-d', $leave->start_date);
                    $end_date = \DateTime::createFromFormat('Y-m-d', $leave->end_date);
                    if ($start_date && $end_date) {
                        $interval = $start_date->diff($end_date);
                        $days = $interval->days + 1; // Incluye el último día
                        $leave->duration = $days . ' ' . ($days > 1 ? 'Días' : 'Día');
                        $leave->requested_period = $start_date->format('d/m/Y') . "\n" . $end_date->format('d/m/Y');
                    }
                } elseif ($leave->start_time && $leave->end_time) {
                    $start_date = \DateTime::createFromFormat('Y-m-d', $leave->start_date);
                    $start_time = \DateTime::createFromFormat('H:i:s', $leave->start_time);
                    $end_time = \DateTime::createFromFormat('H:i:s', $leave->end_time);
                    if ($start_date && $start_time && $end_time) {
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
                        // Formatear requested_period para mostrar la fecha y horas en líneas separadas
                        $leave->requested_period = $start_date->format('d/m/Y') . "\n" . $start_time->format('H:i') . ' - ' . $end_time->format('H:i');
                    }
                } else {
                    $start_date = \DateTime::createFromFormat('Y-m-d', $leave->start_date);
                    $leave->duration = null;
                    $leave->requested_period = $start_date ? $start_date->format('d/m/Y') : null;
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



    // Obtener las solicitudes de permiso de un empleado que solicita el permiso
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

            // Ordenar los resultados por el id en orden descendente
            $query->orderBy('id', 'desc');

            $leaves = $query->with([
                'leaveType' => function ($query) {
                    $query->withTrashed()->select('id', 'name');
                },
                'state',
                'comments',
                'comments' => function ($query) {
                    $query->with([
                        'rejectionReason' => function ($query) {
                            $query->withTrashed();
                        }
                    ]);
                },
                'comments.commentedBy:id,position_id,first_name,second_name,last_name,second_last_name',
                'comments.commentedBy.position:id,name'
            ])->get();

            // Formatear los comentarios y otros campos
            $leaves->each(function ($leave) {
                // Filtrar y segmentar los comentarios
                $comentario_1 = null;
                $comentario_2 = null;

                $leave->comments->each(function ($comment) use (&$comentario_1, &$comentario_2) {
                    $interactionDate = $comment->created_at != $comment->updated_at ? $comment->updated_at : null;
                    $comment_data = [
                        'id' => $comment->id,
                        'comment' => $comment->comment,
                        'commented_by_full_name' => $comment->commentedBy ? $comment->commentedBy->getFullNameAttribute() : null,
                        'commented_by_name' => $comment->commentedBy ? $comment->commentedBy->getNameAttribute() : null,
                        'commented_by_position' => $comment->commentedBy && $comment->commentedBy->position ? $comment->commentedBy->position->name : null,
                        'rejection_reason' => $comment->rejectionReason ? $comment->rejectionReason->reason : null,
                        'action' => $comment->action ? $comment->action : null,
                        'interaction_date' => $interactionDate,
                    ];

                    // Asignar comentarios basados en el orden de los comentarios
                    if ($comentario_1 === null) {
                        $comentario_1 = $comment_data;
                    } else {
                        $comentario_2 = $comment_data;
                    }
                });

                // Añadir comentarios segmentados a la solicitud
                if ($comentario_1) {
                    $leave->comentario_1 = $comentario_1;
                }
                if ($comentario_2) {
                    $leave->comentario_2 = $comentario_2;
                }

                // Calcular la duración del permiso
                if ($leave->start_date && $leave->end_date) {
                    $start_date = \DateTime::createFromFormat('Y-m-d', $leave->start_date);
                    $end_date = \DateTime::createFromFormat('Y-m-d', $leave->end_date);
                    if ($start_date && $end_date) {
                        $interval = $start_date->diff($end_date);
                        $days = $interval->days + 1; // Incluye el último día
                        $leave->duration = $days . ' ' . ($days > 1 ? 'Días' : 'Día');
                        $leave->requested_period = $start_date->format('d/m/Y') . "\n" . $end_date->format('d/m/Y');
                    }
                } elseif ($leave->start_time && $leave->end_time) {
                    $start_date = \DateTime::createFromFormat('Y-m-d', $leave->start_date);
                    $start_time = \DateTime::createFromFormat('H:i:s', $leave->start_time);
                    $end_time = \DateTime::createFromFormat('H:i:s', $leave->end_time);
                    if ($start_date && $start_time && $end_time) {
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
                        // Formatear requested_period para mostrar la fecha y horas en líneas separadas
                        $leave->requested_period = $start_date->format('d/m/Y') . "\n" . $start_time->format('H:i') . ' - ' . $end_time->format('H:i');
                    }
                } else {
                    $start_date = \DateTime::createFromFormat('Y-m-d', $leave->start_date);
                    $leave->duration = null;
                    $leave->requested_period = $start_date ? $start_date->format('d/m/Y') : null;
                }

                // Ocultar los campos innecesarios en Leave
                $leave->makeHidden(['leave_type_id', 'state_id', 'comments']);
            });

            return $this->successResponse('Solicitudes de permisos obtenidas con éxito', $leaves);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener las solicitudes de permisos: ' . $e->getMessage(), 500);
        }
    }

    // Obtener las estadísticas de permisos de un empleado
// Obtener las estadísticas de permisos de un empleado
    public function getLeaveStatistics(int $employee_id): JsonResponse
    {
        try {
            $totalPermissions = Leave::where('employee_id', $employee_id)->count();
            $approvedPermissions = Leave::where('employee_id', $employee_id)->whereHas('state', function ($q) {
                $q->where('name', 'Aprobado');
            })->count();
            $disapprovedPermissions = Leave::where('employee_id', $employee_id)->whereHas('state', function ($q) {
                $q->where('name', 'Rechazado');
            })->count();

            $leaveTypes = LeaveType::all();
            $leaveTypeDurations = [];

            foreach ($leaveTypes as $leaveType) {
                $leaveTypeDurationInDays = 0;
                $leaveTypeDurationInHours = 0;
                $leaveTypeDurationInMinutes = 0;

                $leaves = Leave::where('employee_id', $employee_id)
                    ->where('leave_type_id', $leaveType->id)
                    ->whereHas('state', function ($q) {
                        $q->where('name', 'Aprobado');
                    })
                    ->get();

                foreach ($leaves as $leave) {
                    if ($leave->start_date && $leave->end_date) {
                        $start_date = new \DateTime($leave->start_date);
                        $end_date = new \DateTime($leave->end_date);
                        $interval = $start_date->diff($end_date);
                        $days = $interval->days + 1; // Incluye el último día
                        $leaveTypeDurationInDays += $days;
                    } elseif ($leave->start_time && $leave->end_time) {
                        $start_time = new \DateTime($leave->start_time);
                        $end_time = new \DateTime($leave->end_time);
                        $interval = $start_time->diff($end_time);
                        $hours = $interval->h;
                        $minutes = $interval->i;
                        $leaveTypeDurationInHours += $hours;
                        $leaveTypeDurationInMinutes += $minutes;
                    }
                }

                // Convertir minutos adicionales en horas
                $additionalHours = intdiv($leaveTypeDurationInMinutes, 60);
                $remainingMinutes = $leaveTypeDurationInMinutes % 60;
                $leaveTypeDurationInHours += $additionalHours;

                // Formatear en HH:MM
                $totalDurationFormatted = sprintf('%02d:%02d', $leaveTypeDurationInHours, $remainingMinutes);

                $leaveTypeDurations[$leaveType->name] = [
                    'total_in_days' => $leaveTypeDurationInDays,
                    'total_in_hours' => $totalDurationFormatted,
                ];
            }

            $data = [
                'totalPermissions' => $totalPermissions,
                'approvedPermissions' => $approvedPermissions,
                'disapprovedPermissions' => $disapprovedPermissions,
                'leaveTypeDurations' => $leaveTypeDurations,
            ];

            return $this->successResponse('Estadísticas de permisos obtenidas con éxito', $data);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener las estadísticas de permisos: ' . $e->getMessage(), 500);
        }
    }


}

