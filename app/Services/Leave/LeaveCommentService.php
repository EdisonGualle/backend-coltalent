<?php

namespace App\Services\Leave;

use App\Events\NotificationEvent;
use App\Mail\ApproverActionNotificationMail;
use App\Models\Leave\LeaveComment;
use App\Models\Leave\Leave;
use App\Models\Leave\LeaveState;
use App\Models\Employee\Employee;
use App\Models\Notification;
use App\Models\User;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Mail\LeaveActionNotificationMail;
use App\Mail\PendingApprovalNotificationMail;
use App\Models\Organization\Position;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
class LeaveCommentService extends ResponseService
{
    public function updateCommentAction(int $employee_id, int $comment_id, array $data): JsonResponse
    {
        DB::beginTransaction();

        try {
            $comment = LeaveComment::findOrFail($comment_id);
            $leave = $comment->leave;

            // Validar que el comentario está asignado al empleado correcto
            if ($comment->commented_by !== $employee_id) {
                throw new \Exception("El empleado no está autorizado para realizar esta acción");
            }

            // Validar que el comentario está en estado Pendiente
            if ($comment->action !== 'Pendiente') {
                throw new \Exception("No se puede editar una aprobacion una vez interactuado");
            }

            // Actualizar el comentario con la nueva acción y posible razón de rechazo
            $comment->update([
                'action' => $data['action'],
                'comment' => $data['comment'] ?? null,
                'rejection_reason_id' => $data['rejection_reason_id'] ?? null,
            ]);

            // Manejar las diferentes acciones
            if ($data['action'] === 'Aprobado') {
                $this->handleApproval($leave, $employee_id);
            } elseif ($data['action'] === 'Rechazado' || $data['action'] === 'Corregir') {
                $this->handleRejectionOrCorrection($leave, $data['action']);
                $this->createNotification($leave, $employee_id, $data['action']);
            }


            if (in_array($data['action'], ['Aprobado', 'Rechazado'])) {
                $this->sendActionNotificationEmail($leave, $employee_id, $data['action']);
            }

            // Marcar las notificaciones de "Solicitud pendiente" como leídas
            $this->markNotificationsAsRead($leave->id, $employee_id);

            DB::commit();
            return $this->successResponse('Acción realizada con éxito', $comment);
        } catch (\Exception $e) {
            DB::rollBack();

              
        // Registrar el error en los logs
        Log::error('Error al realizar la acción sobre el comentario: ' . $e->getMessage(), ['exception' => $e]);
            return $this->errorResponse('Error al realizar la acción: ' . $e->getMessage(), 500);
        }
    }


    private function sendActionNotificationEmail(Leave $leave, int $approver_id, string $action)
    {
        $approver = Employee::findOrFail($approver_id);
        $applicant = $leave->employee;

        $approverName = $approver->full_name;
        $actionDescription = match ($action) {
            'Aprobado' => 'aprobado',
            'Rechazado' => 'rechazado',
            default => 'realizado una acción sobre',
        };

        // Determinar si es la primera aprobación, la aprobación final o un rechazo
        $isRejection = $action === 'Rechazado';
        $isFinalApproval = !$this->getNextApprover($leave, $approver_id) && !$isRejection;
        $approvalStage = $isRejection ? 'Rechazo' : ($isFinalApproval ? 'Aprobación Final' : 'Primera Aprobación');
        $headerColor = $isRejection ? 'red' : ($isFinalApproval ? 'green' : 'blue');
        $subjectApplicant = "Tu solicitud de permiso ha sido {$actionDescription} - {$approvalStage}";
        $subjectApprover = "Notificación de Acción Realizada";

        // Datos adicionales para el correo
        $nextApprover = $isRejection ? null : $this->getNextApprover($leave, $approver_id);
        $evaluationDate = Carbon::now()->format('d/m/Y H:i');
        $comment = $leave->comments()->where('commented_by', $approver_id)->orderBy('created_at', 'desc')->first()->comment ?? '';
        $rejectionReason = $isRejection ? $leave->comments()->where('commented_by', $approver_id)->orderBy('created_at', 'desc')->first()->rejectionReason->reason ?? '' : '';

        $mailData = [
            'employeeName' => $applicant->getFullNameAttribute(),
            'startDate' => $leave->start_date ? Carbon::parse($leave->start_date)->format('d/m/Y') : 'N/A',
            'endDate' => $leave->end_date ? Carbon::parse($leave->end_date)->format('d/m/Y') : null,
            'startTime' => $leave->start_time ? Carbon::parse($leave->start_time)->format('H:i') : null,
            'endTime' => $leave->end_time ? Carbon::parse($leave->end_time)->format('H:i') : null,
            'duration' => $this->calculateDuration($leave),
            'leaveType' => $leave->leaveType->name,
            'leaveReason' => $leave->reason,
            'approverName' => $approverName,
            'action' => $actionDescription,
            'isFinalApproval' => $isFinalApproval,
            'isRejection' => $isRejection,
            'evaluationDate' => $evaluationDate,
            'comment' => $comment,
            'rejectionReason' => $rejectionReason,
            'nextApprover' => $nextApprover ? $nextApprover->getFullNameAttribute() : null,
            'headerColor' => $headerColor,
        ];

        Mail::to($applicant->user->email)->send(new LeaveActionNotificationMail($mailData, $subjectApplicant));
        Mail::to($approver->user->email)->send(new ApproverActionNotificationMail($mailData, $subjectApprover));  // Nuevo correo al aprobador
    }

    // Añadir este método para calcular la duración del permiso
    private function calculateDuration(Leave $leave)
    {
        if ($leave->start_date && $leave->end_date) {
            $start_date = Carbon::parse($leave->start_date);
            $end_date = Carbon::parse($leave->end_date);
            $interval = $start_date->diff($end_date);
            $days = $interval->days + 1; // Incluye el último día
            return $days . ' ' . ($days > 1 ? 'días' : 'día');
        } elseif ($leave->start_time && $leave->end_time) {
            $start_time = Carbon::parse($leave->start_time);
            $end_time = Carbon::parse($leave->end_time);
            $interval = $start_time->diff($end_time);
            $hours = $interval->h;
            $minutes = $interval->i;
            $duration = '';
            if ($hours > 0) {
                $duration .= $hours . ' ' . ($hours > 1 ? 'horas' : 'hora');
            }
            if ($minutes > 0) {
                if ($hours > 0) {
                    $duration .= ' y ';
                }
                $duration .= $minutes . ' ' . ($minutes > 1 ? 'minutos' : 'minuto');
            }
            return $duration;
        }
        return 'N/A';
    }

    private function markNotificationsAsRead(int $leave_id, int $employee_id)
    {
        Notification::where('type', 'Solicitud pendiente')
            ->where('data->leave_id', $leave_id)
            ->where('data->approver_id', $employee_id)
            ->update(['read_at' => now()]);
    }


    private function handleApproval(Leave $leave, int $approver_id)
    {
        $nextApprover = $this->getNextApprover($leave, $approver_id);
    
        // Determinar el nivel actual de aprobación basado en los comentarios existentes
        $approvedComments = $leave->comments()->where('action', 'Aprobado')->count();
        $approvalStage = match ($approvedComments) {
            1 => 'Primera aprobación',
            2 => 'Segunda aprobación',
            3 => 'Aprobación final',
            default => 'Acción realizada',
        };
    
        if ($nextApprover) {
            // Crear comentario para el siguiente aprobador
            LeaveComment::create([
                'leave_id' => $leave->id,
                'commented_by' => $nextApprover->id,
                'action' => 'Pendiente',
            ]);
    
            // Actualizar el estado a Pendiente para el siguiente nivel de aprobación
            $leave->update(['state_id' => $this->getStateId('Pendiente')]);
    
            // Crear notificación para la acción actual
            $this->createNotification($leave, $approver_id, $approvalStage);
    
            // Crear notificación pendiente para el próximo aprobador
            $this->createPendingNotification($leave, $nextApprover->id);
    
            // Enviar correo al próximo aprobador
            $this->sendPendingApprovalNotificationEmail($leave, $nextApprover);
        } else {
            // Si no hay más aprobadores, aprobar definitivamente
            $leave->update(['state_id' => $this->getStateId('Aprobado')]);
    
            // Crear notificación final de aprobación
            $this->createNotification($leave, $approver_id, 'Aprobación final');
        }
    }
    
    
    

    private function getNextApprover(Leave $leave, int $approver_id)
    {
        // Verificar si el permiso ya está aprobado
        if ($leave->state_id === $this->getStateId('Aprobado')) {
            return null; // Fin del flujo
        }
    
        // Obtener al solicitante del permiso
        $applicant = Employee::with('position.unit.direction')->find($leave->employee_id);
    
        if (!$applicant || !$applicant->position) {
            throw new \Exception("No se pudo determinar la posición del solicitante");
        }
    
        $position = $applicant->position;
    
        // Obtener la unidad del Administrador (Jefe de Talento Humano)
        $admin = Employee::whereHas('user.role', function ($query) {
            $query->where('name', 'Administrador');
        })->first();
    
        if (!$admin || !$admin->position->unit) {
            throw new \Exception("No se pudo determinar la unidad del Jefe de talento humano");
        }
    
        $talentoHumanoUnitId = $admin->position->unit->id;
    
        // Obtener al Jefe General
        $jefeGeneral = Employee::whereHas('user.role', function ($query) {
            $query->where('name', 'Jefe General');
        })->first();
    
        if (!$jefeGeneral) {
            throw new \Exception("No se pudo determinar al Jefe General");
        }
    
        // Verificar el historial de aprobaciones para determinar el nivel actual
        $comments = $leave->comments()->orderBy('created_at')->get();
        $currentLevel = $comments->count();
    
        // Caso 6: Solicitante es Jefe General
        if ($applicant->id === $jefeGeneral->id) {
            if ($currentLevel === 0) {
                return $admin; // Solo el Administrador aprueba
            }
            if ($currentLevel === 1) {
                return null; // Fin del flujo
            }
        }
    
        // Caso 5: Solicitante es Jefe de Dirección
        if ($position->is_manager && $position->direction && !$position->unit) {
            if ($currentLevel === 0) {
                return $jefeGeneral; // Nivel 1: Jefe General
            }
            if ($currentLevel === 1) {
                return ($admin && $admin->id === $approver_id) ? null : $admin; // Nivel 2: Administrador
            }
        }
    
        // Caso 4: Solicitante es Jefe de una Unidad
        if ($position->is_manager && $position->unit) {
            if ($position->unit->id === $talentoHumanoUnitId) {
                // Subcaso 4.1: Solicitante es el Jefe de Talento Humano
                if ($currentLevel === 0) {
                    return $position->unit->direction->managerEmployee->employee ?? null;
                }
                if ($currentLevel === 1) {
                    return null; // Fin del flujo
                }
            } else {
                // Subcaso 4.2: Solicitante es Jefe de otra Unidad
                if ($currentLevel === 0) {
                    return $position->unit->direction->managerEmployee->employee ?? null;
                }
                if ($currentLevel === 1) {
                    return ($admin && $admin->id === $approver_id) ? null : $admin;
                }
            }
        }
    
        // Caso 1: Empleado de una Unidad
        if ($position->unit && $position->unit->id !== $talentoHumanoUnitId) {
            if ($currentLevel === 0) {
                return $position->unit->managerEmployee->employee ?? null;
            }
            if ($currentLevel === 1) {
                return $position->unit->direction->managerEmployee->employee ?? null;
            }
            if ($currentLevel === 2) {
                return ($admin && $admin->id === $approver_id) ? null : $admin;
            }
        }
    
        // Caso 2: Empleado de Talento Humano
        if ($position->unit && $position->unit->id === $talentoHumanoUnitId) {
            if ($currentLevel === 0) {
                return $admin;
            }
            if ($currentLevel === 1) {
                return $position->unit->direction->managerEmployee->employee ?? null;
            }
        }
    
        // Caso 3: Empleado de una Dirección (sin unidad)
        if (!$position->unit && $position->direction) {
            if ($currentLevel === 0) {
                return $position->direction->managerEmployee->employee ?? null;
            }
            if ($currentLevel === 1) {
                return ($admin && $admin->id === $approver_id) ? null : $admin;
            }
        }
    
        // Si no es un caso válido, retornar null
        return null;
    }
    
    
    private function sendPendingApprovalNotificationEmail(Leave $leave, Employee $nextApprover)
    {
        $applicant = $leave->employee;

        $mailData = [
            'approverName' => $nextApprover->getFullNameAttribute(),
            'applicantName' => $applicant->getFullNameAttribute(),
            'startDate' => $leave->start_date ? Carbon::parse($leave->start_date)->format('d/m/Y') : 'N/A',
            'endDate' => $leave->end_date ? Carbon::parse($leave->end_date)->format('d/m/Y') : null,
            'startTime' => $leave->start_time ? Carbon::parse($leave->start_time)->format('H:i') : null,
            'endTime' => $leave->end_time ? Carbon::parse($leave->end_time)->format('H:i') : null,
            'duration' => $this->calculateDuration($leave),
            'leaveType' => $leave->leaveType->name,
            'leaveReason' => $leave->reason,
        ];

        $subject = "Nueva Solicitud de Permiso Pendiente";

        Mail::to($nextApprover->user->email)->send(new PendingApprovalNotificationMail($mailData, $subject));
    }


    private function createNotification(Leave $leave, int $employee_id, string $action)
    {
        $approver = Employee::find($employee_id);
        $approverName = $approver ? $approver->full_name : 'Aprobador';
        $approverPhoto = $approver ? $approver->user->photo : null;
        $startDate = Carbon::parse($leave->start_date)->format('d-m-Y');
    
        // Generar mensaje dinámico basado en la acción
        $message = match ($action) {
            'Primera aprobación' => "✅ Tu solicitud para el {$startDate} ha sido aprobada por {$approverName}. ¡Sigue pendiente de la próxima aprobación! 🎯",
            'Segunda aprobación' => "👏 ¡Paso importante! La solicitud para el {$startDate} ha sido revisada por {$approverName}. Solo falta la aprobación final.",
            'Aprobación final' => "🎉 ¡Felicidades! Tu permiso para el {$startDate} ha sido aprobado definitivamente por {$approverName}.",
            'Rechazado' => "🚫 Tu solicitud para el {$startDate} fue rechazada por {$approverName}. Comunícate con ellos para más detalles.",
            'Corregir' => "🔄 Necesitamos algunos ajustes en tu solicitud para el {$startDate}. Revisa los comentarios de {$approverName} y actualízala lo antes posible.",
            default => "ℹ️ Se realizó una acción sobre tu solicitud. Verifica los detalles.",
        };
    
        // Obtener el usuario asociado al empleado que recibe la notificación
        $user = User::where('employee_id', $leave->employee_id)->first();
    
        if (!$user) {
            throw new \Exception("No se encontró el usuario asociado al empleado con ID: {$leave->employee_id}");
        }
    
        // Crear la notificación
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $action,
            'data' => [
                'leave_id' => $leave->id,
                'message' => $message,
                'approver_id' => $employee_id,
                'approver_photo' => $approverPhoto,
            ],
        ]);
    
        // Disparar evento de notificación
        event(new NotificationEvent($notification));
    }
    
    private function createPendingNotification(Leave $leave, int $approver_id)
    {
        $approver = Employee::find($approver_id);
        $approverName = $approver ? $approver->full_name : 'Aprobador';
        $approverPhoto = $approver ? $approver->user->photo : null;
        $applicantPhoto = $leave->employee && $leave->employee->user && $leave->employee->user->photo ? $leave->employee->user->photo : null;
        $startDate = Carbon::parse($leave->start_date)->format('d-m-Y');

        // Obtener el nombre del solicitante
        $applicant = Employee::find($leave->employee_id);
        $applicantName = $applicant ? $applicant->full_name : 'Solicitante';

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
    }




    private function handleRejectionOrCorrection(Leave $leave, string $action)
    {
        $leave->update(['state_id' => $this->getStateId($action)]);
    }


    
    
    private function getStateId(string $stateName)
    {
        return LeaveState::where('name', $stateName)->first()->id;
    }
}
