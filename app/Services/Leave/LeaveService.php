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
use App\Models\Leave\Delegation;
use App\Models\Other\Configuration;
use Illuminate\Support\Facades\Log;

class LeaveService extends ResponseService
{
    public function createLeave(int $employee_id, array $data): JsonResponse
    {
        DB::beginTransaction();

        try {
            // Establecer el estado inicial de la solicitud de permiso
            $data['employee_id'] = $employee_id;
            $data['state_id'] = LeaveState::where('name', 'Pendiente')->first()->id;

            // Validación: verificar si el empleado tiene una subrogación activa para el rango de fechas
            try {
                $activeDelegationExists = Delegation::where('delegate_id', $employee_id)
                    ->where('status', 'Activa') // Filtrar solo delegaciones activas
                    ->whereHas('leave', function ($query) use ($data) {
                        $query->where(function ($subQuery) use ($data) {
                            $subQuery->whereBetween('start_date', [$data['start_date'], $data['end_date']])
                                     ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']]);
                        });
                    })
                    ->exists();
            
                if ($activeDelegationExists) {
                    return $this->errorResponse("El empleado tiene una subrogación activa para el rango de fechas solicitado.", 400);
                }
            } catch (\Exception $e) {
                return $this->errorResponse("Error al verificar subrogaciones activas: " . $e->getMessage(), 500);
            }
            
            // Crear la solicitud de permiso
            $leave = Leave::create($data);

            // Obtener el empleado que solicita el permiso
            $employee = Employee::findOrFail($employee_id);

            // Determinar el aprobador
            $approverId = $this->determineApprover($employee);

            // Crear el comentario inicial asignado al aprobador
            LeaveComment::create([
                'leave_id' => $leave->id,
                'commented_by' => $approverId,
                'action' => 'Pendiente'
            ]);

            // Verificar que el aprobador tenga un usuario asociado antes de enviar la notificación
            $approver = Employee::findOrFail($approverId);

            if (!$approver->user) {
                return $this->errorResponse("El aprobador no tiene un usuario asociado.", 400);
            }

            // Crear la notificación para el primer aprobador
            $this->createPendingNotification($leave, $approverId);

            // Enviar correo de constancia al empleado solicitante
            $approver = Employee::findOrFail($approverId);
            
            if (!$employee->user->email) {
                return $this->errorResponse("El empleado no tiene un correo electrónico válido.", 400);
            }

            Mail::to($employee->user->email)->queue(new LeaveRequestedMail($employee, $leave, $approver));

            DB::commit();

            return $this->successResponse('Solicitud de permiso creada con éxito', $leave, 201);
        } catch (\Exception $e) {
            DB::rollBack();

            // Registrar el mensaje del error en los logs
            Log::error('Error al crear la solicitud de permiso: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString() // Agregar el stack trace para mayor detalle
            ]);
            return $this->errorResponse('No se pudo crear la solicitud de permiso: ' . $e->getMessage(), 500);
        }
    }

    private function determineApprover(Employee $employee): int
    {
        if ($employee->position->is_general_manager) {
            // Caso 1: Jefe General
            $admin = Employee::whereHas('user', function ($query) {
                $query->whereHas('role', function ($q) {
                    $q->where('name', 'Administrador');
                });
            })->first();

            if (!$admin) {
                throw new \Exception("No se encontró el jefe de talento humano");
            }

            return $admin->id;
        } elseif ($employee->position->is_manager && $employee->position->direction_id) {
            // Caso 2: Jefe de Dirección
            $generalManager = Position::where('is_general_manager', true)->first();

            if (!$generalManager || !$generalManager->employee) {
                throw new \Exception("No se encontró el alcalde");
            }

            return $generalManager->employee->id;
        } elseif ($employee->position->is_manager && $employee->position->unit_id) {
            // Caso 3: Jefe de Unidad
            // Buscar el jefe de la dirección correspondiente a la unidad
            $unit = Unit::findOrFail($employee->position->unit_id);
            $directionManager = Position::where('direction_id', $unit->direction_id)
                ->where('is_manager', true)
                ->first();

            if (!$directionManager || !$directionManager->employee) {
                throw new \Exception("No se encontró el jefe de la dirección correspondiente a la unidad");
            }

            return $directionManager->employee->id;
        } elseif ($employee->position->unit_id) {
            // Caso 4: Empleado de una Unidad
            // Buscar el jefe de la unidad
            $unitManager = Position::where('unit_id', $employee->position->unit_id)
                ->where('is_manager', true)
                ->first();

            if (!$unitManager || !$unitManager->employee) {
                throw new \Exception("No se encontró el jefe de la unidad");
            }

            return $unitManager->employee->id;
        } elseif ($employee->position->direction_id) {
            // Caso 5: Empleado de una Dirección
            // Buscar el jefe de la dirección
            $directionManager = Position::where('direction_id', $employee->position->direction_id)
                ->where('is_manager', true)
                ->first();

            if (!$directionManager || !$directionManager->employee) {
                throw new \Exception("No se encontró el jefe de la dirección");
            }

            return $directionManager->employee->id;
        } else {
            // Caso 6: Sin Asignación de Aprobador
            throw new \Exception("No se pudo determinar el aprobador para el empleado");
        }
    }

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

        // Validar que el usuario tenga una dirección de correo
        if (!$user->email) {
            throw new \Exception("El usuario no tiene una dirección de correo válida");
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
        Mail::to($user->email)->queue(new ApprovalNotificationMail($approver, $leave, $leave->employee));
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

            $query = Leave::query();

            switch ($filter) {
                case 'pendientes':
                    $query->whereHas('comments', function ($q) use ($employee_id) {
                        $q->where('commented_by', $employee_id)
                            ->where('action', 'Pendiente');
                    });
                    break;
                case 'aprobados':
                    $query->whereHas('comments', function ($q) use ($employee_id) {
                        $q->where('commented_by', $employee_id)
                            ->where('action', 'Aprobado');
                    });
                    break;
                case 'rechazados':
                    $query->whereHas('comments', function ($q) use ($employee_id) {
                        $q->where('commented_by', $employee_id)
                            ->where('action', 'Rechazado');
                    });
                    break;
                case 'Corregir':
                    $query->whereHas('comments', function ($q) use ($employee_id) {
                        $q->where('commented_by', $employee_id)
                            ->where('action', 'Corregir');
                    });
                    break;
                case 'historial':
                    $query->whereHas('comments', function ($q) use ($employee_id) {
                        $q->where('commented_by', $employee_id);
                    });
                    break;
                default:
                    break;
            }

            // Ordenar los resultados por el id en orden descendente
            $query->orderBy('id', 'desc');

            // Cargar las relaciones necesarias para el listado
            $leaves = $query->with([
                'employee:id,identification,position_id,first_name,second_name,last_name,second_last_name',
                'employee.position:id,name,unit_id,direction_id',
                'employee.position.unit:id,name,direction_id',
                'employee.position.unit.direction:id,name',
                'employee.position.direction:id,name',
                'employee.position.responsibilities:id,position_id,name',
                'leaveType' => function ($query) {
                    $query->withTrashed()->select('id', 'name');
                },
                'state:id,name',
                'comments' => function ($query) {
                    $query->with([
                        'rejectionReason' => function ($query) {
                            $query->withTrashed();
                        },
                        'commentedBy:id,position_id,first_name,second_name,last_name,second_last_name',
                        'commentedBy.position:id,name',
                    ]);
                },
                'delegations' => function ($query) {
                    $query->with([
                        'delegate:id,first_name,second_name,last_name,second_last_name',
                        'responsibilities:id,name',
                    ]);
                },
            ])->get();

            // Obtener la configuración para los días mínimos de subrogación
            $minDaysForSubrogation = Configuration::where('key', 'min_days_for_subrogation')->value('value');

            // Formatear datos
            $leaves->each(function ($leave) use ($minDaysForSubrogation) {
                $comments = $leave->comments;

                // Construir approval_steps
                $formattedComments = [];
                foreach ($comments as $index => $comment) {
                    $stepData = [
                        'step' => $index + 1,
                        'id' => $comment->id,
                        'approver' => [
                            'id' => $comment->commentedBy?->id,
                            'full_name' => $comment->commentedBy?->getFullNameAttribute(),
                            'short_name' => $comment->commentedBy?->getNameAttribute(),
                            'photo' => $comment->commentedBy?->userPhoto(),
                            'position' => $comment->commentedBy?->position?->name,
                        ],
                        'decision' => [
                            'action' => $comment->action,
                            'comment' => $comment->comment,
                            'rejection_reason' => $comment->rejectionReason?->reason,
                        ],
                        'interaction_date' => $comment->created_at != $comment->updated_at ? $comment->updated_at : null,
                    ];

                    // Agregar subrogation solo al primer paso
                    if ($index === 0) {
                        // Validar si la subrogación existe y formatearla
                        $stepData['subrogation'] = $leave->delegations->map(function ($delegation) {
                            return [
                                'delegate' => [
                                    'id' => $delegation->delegate?->id,
                                    'full_name' => $delegation->delegate?->getFullNameAttribute(),
                                    'short_name' => $delegation->delegate?->getNameAttribute(),
                                ],
                                'responsibilities' => $delegation->responsibilities->map(function ($responsibility) {
                                    return [
                                        'id' => $responsibility->id,
                                        'name' => $responsibility->name,
                                    ];
                                }),
                            ];
                        });
                    }
                    $formattedComments[] = $stepData;
                }

                // Asignar los comentarios como un array único
                $leave->approval_steps = $formattedComments;

                // Obtener la dirección de la unidad si existe, o directamente la dirección del puesto
                $direction = $leave->employee->position->unit
                    ? $leave->employee->position->unit->direction
                    : $leave->employee->position->direction;

                $unit = $leave->employee->position->unit;

                $leave->employee->direction_name = $direction ? $direction->name : null;
                $leave->employee->unit_name = $unit ? $unit->name : null;

                // Añadir full_name, name y position_name del empleado
                $leave->employee->full_name = $leave->employee->getFullNameAttribute();
                $leave->employee->short_name = $leave->employee->getNameAttribute();
                $leave->employee->position_name = $leave->employee->position ? $leave->employee->position->name : null;

                // Calcular la duración del permiso
                if ($leave->start_date && $leave->end_date) {
                    $start_date = \DateTime::createFromFormat('Y-m-d', $leave->start_date);
                    $end_date = \DateTime::createFromFormat('Y-m-d', $leave->end_date);
                    if ($start_date && $end_date) {
                        $interval = $start_date->diff($end_date);
                        $days = $interval->days + 1; // Incluye el último día
                        $leave->duration = $days . ' ' . ($days > 1 ? 'Días' : 'Día');
                        $leave->requested_period = [
                            'start' => $start_date->format('d/m/Y'),
                            'end' => $end_date->format('d/m/Y')
                        ];

                        // Determinar si requiere subrogación
                        $leave->requires_subrogation = $days >= $minDaysForSubrogation;

                        if ($leave->requires_subrogation) {
                            $leave->responsibilities = $leave->employee->position->responsibilities->map(function ($responsibility) {
                                return [
                                    'id' => $responsibility->id,
                                    'name' => $responsibility->name,
                                ];
                            });
                        }

                    }
                } elseif ($leave->start_time && $leave->end_time) {
                    $start_date = \DateTime::createFromFormat('Y-m-d', $leave->start_date);
                    $start_time = \DateTime::createFromFormat('H:i:s', $leave->start_time);
                    $end_time = \DateTime::createFromFormat('H:i:s', $leave->end_time);
                    if ($start_date && $start_time && $end_time) {
                        $interval = $start_time->diff($end_time);
                        $hours = $interval->h;
                        $minutes = $interval->i;

                        if ($hours === 0) {
                            $leave->duration = $minutes . ' ' . ($minutes > 1 ? 'Minutos' : 'Minuto');
                        } else {
                            $leave->duration = $hours . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ' Horas';
                        }

                        $leave->requested_period = [
                            'date' => $start_date->format('d/m/Y'),
                            'start_time' => $start_time->format('H:i'),
                            'end_time' => $end_time->format('H:i')
                        ];
                        $leave->requires_subrogation = false;
                    }
                } else {
                    $leave->duration = null;
                    $leave->requested_period = null;
                    $leave->requires_subrogation = false;
                    $leave->responsibilities = [];

                }

                // Ocultar los campos innecesarios
                $leave->employee->makeHidden(['first_name', 'second_name', 'last_name', 'second_last_name', 'position']);
                $leave->makeHidden(['employee_id', 'leave_type_id', 'state_id', 'comments', 'delegations']);
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
            // Construir la consulta base para el empleado
            $query = Leave::where('employee_id', $employee_id);

            // Aplicar filtros según el estado
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
                    // No aplicar filtros, obtener todas las solicitudes
                    break;
            }

            // Ordenar los resultados por ID en orden descendente
            $query->orderBy('id', 'desc');

            // Cargar las relaciones necesarias
            $leaves = $query->with([
                'leaveType' => function ($query) {
                    $query->withTrashed()->select('id', 'name');
                },
                'state:id,name',
                'comments' => function ($query) {
                    $query->with([
                        'rejectionReason' => function ($query) {
                            $query->withTrashed();
                        },
                        'commentedBy:id,position_id,first_name,second_name,last_name,second_last_name',
                        'commentedBy.position:id,name',
                    ]);
                },
                'employee' => function ($query) {
                    $query->select('id', 'identification', 'position_id', 'first_name', 'second_name', 'last_name', 'second_last_name')
                        ->with([
                            'position:id,unit_id,direction_id,name',
                            'position.unit:id,direction_id,name',
                            'position.unit.direction:id,name',
                            'position.direction:id,name',
                        ]);
                },
            ])->get();

            // Formatear los datos de cada solicitud
            $leaves->each(function ($leave) {
                // Procesar comentarios
                $formattedComments = $leave->comments->map(function ($comment, $index) {
                    $interactionDate = $comment->created_at != $comment->updated_at ? $comment->updated_at : null;

                    return [
                        'step' => $index + 1,
                        'id' => $comment->id,
                        'approver' => [
                            'id' => $comment->commentedBy ? $comment->commentedBy->id : null,
                            'full_name' => $comment->commentedBy ? $comment->commentedBy->getFullNameAttribute() : null,
                            'short_name' => $comment->commentedBy ? $comment->commentedBy->getNameAttribute() : null,
                            'photo' => $comment->commentedBy ? $comment->commentedBy->userPhoto() : null,
                            'position' => $comment->commentedBy && $comment->commentedBy->position ? $comment->commentedBy->position->name : null,
                        ],
                        'decision' => [
                            'action' => $comment->action ? $comment->action : null,
                            'comment' => $comment->comment ? $comment->comment : null,
                            'rejection_reason' => $comment->rejectionReason ? $comment->rejectionReason->reason : null,
                        ],
                        'interaction_date' => $interactionDate,
                    ];
                });

                $leave->approval_steps = $formattedComments;

                // Calcular la duración del permiso
                if ($leave->start_date && $leave->end_date) {
                    $start_date = \DateTime::createFromFormat('Y-m-d', $leave->start_date);
                    $end_date = \DateTime::createFromFormat('Y-m-d', $leave->end_date);
                    if ($start_date && $end_date) {
                        $interval = $start_date->diff($end_date);
                        $days = $interval->days + 1; // Incluye el último día
                        $leave->duration = $days . ' ' . ($days > 1 ? 'Días' : 'Día');
                        $leave->requested_period = [
                            'start' => $start_date->format('d/m/Y'),
                            'end' => $end_date->format('d/m/Y'),
                        ];
                    }
                } elseif ($leave->start_time && $leave->end_time) {
                    $start_date = \DateTime::createFromFormat('Y-m-d', $leave->start_date);
                    $start_time = \DateTime::createFromFormat('H:i:s', $leave->start_time);
                    $end_time = \DateTime::createFromFormat('H:i:s', $leave->end_time);
                    if ($start_date && $start_time && $end_time) {
                        $interval = $start_time->diff($end_time);
                        $hours = $interval->h;
                        $minutes = $interval->i;
                        $leave->duration = $hours . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ' Horas';
                        $leave->requested_period = [
                            'date' => $start_date->format('d/m/Y'),
                            'start_time' => $start_time->format('H:i'),
                            'end_time' => $end_time->format('H:i'),
                        ];
                    }
                } else {
                    $start_date = \DateTime::createFromFormat('Y-m-d', $leave->start_date);
                    $leave->duration = null;
                    $leave->requested_period = $start_date ? ['date' => $start_date->format('d/m/Y')] : null;
                }

                // Obtener dirección y unidad del empleado
                $direction = $leave->employee->position->unit
                    ? $leave->employee->position->unit->direction
                    : $leave->employee->position->direction;

                $unit = $leave->employee->position->unit;

                $leave->employee->direction_name = $direction ? $direction->name : null;
                $leave->employee->unit_name = $unit ? $unit->name : null;

                // Añadir full_name, name y position_name del empleado
                $leave->employee->full_name = $leave->employee->getFullNameAttribute();
                $leave->employee->short_name = $leave->employee->getNameAttribute();
                $leave->employee->position_name = $leave->employee->position ? $leave->employee->position->name : null;


                // Ocultar los campos innecesarios
                $leave->employee->makeHidden(['first_name', 'second_name', 'last_name', 'second_last_name', 'position']);
                $leave->makeHidden(['leave_type_id', 'state_id', 'comments']);
            });

            return $this->successResponse('Solicitudes de permisos obtenidas con éxito', $leaves);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener las solicitudes de permisos: ' . $e->getMessage(), 500);
        }
    }

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

