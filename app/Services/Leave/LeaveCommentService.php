<?php

namespace App\Services\Leave;

use App\Events\NotificationEvent;
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

            // Marcar las notificaciones de "Solicitud pendiente" como leídas
            $this->markNotificationsAsRead($leave->id, $employee_id);

            DB::commit();
            return $this->successResponse('Acción realizada con éxito', $comment);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al realizar la acción: ' . $e->getMessage(), 500);
        }
    }


    private function markNotificationsAsRead(int $leave_id, int $employee_id)
    {
        Notification::where('type', 'Solicitud pendiente')
            ->where('data->leave_id', $leave_id)
            ->where('data->approver_id', $employee_id)
            ->update(['read_at' => now()]);
    }


    private function handleApproval(Leave $leave, int $employee_id)
    {
        $nextApprover = $this->getNextApprover($leave, $employee_id);
        if ($nextApprover) {
            LeaveComment::create([
                'leave_id' => $leave->id,
                'commented_by' => $nextApprover->id,
                'action' => 'Pendiente',
            ]);
            $leave->update(['state_id' => $this->getStateId('Pendiente')]);
            $this->createNotification($leave, $employee_id, 'Primera aprobación');
            $this->createPendingNotification($leave, $nextApprover->id);
        } else {
            $leave->update(['state_id' => $this->getStateId('Aprobado')]);
            $this->createNotification($leave, $employee_id, 'Aprobación final');
        }
    }


    private function createNotification(Leave $leave, int $employee_id, string $action)
    {
        $approver = Employee::find($employee_id);
        $approverName = $approver ? $approver->full_name : 'Aprobador';
        $approverPhoto = $approver ? $approver->user->photo : null;
        $startDate = Carbon::parse($leave->start_date)->format('d-m-Y');

        $message = match ($action) {
            'Primera aprobación' => "Tu solicitud de permiso para el {$startDate} ha sido aprobada por {$approverName}.",
            'Aprobación final' => "Tu solicitud de permiso para el {$startDate} ha sido aprobada definitivamente por {$approverName}.",
            'Rechazado' => "Tu solicitud de permiso para el {$startDate} ha sido rechazada por {$approverName}.",
            'Corregir' => "Tu solicitud de permiso para el {$startDate} ha sido marcada para corrección por {$approverName}.",
            default => 'Acción realizada.',
        };

        // Obtener el usuario asociado al empleado que recibe la notificación
        $user = User::where('employee_id', $leave->employee_id)->first();

        if (!$user) {
            throw new \Exception("No se encontró el usuario asociado al empleado con ID: {$leave->employee_id}");
        }

        $notification = Notification::create([
            'user_id' => $user->id,  // Usuario que recibe la notificación
            'type' => $action,
            'data' => [
                'leave_id' => $leave->id,
                'message' => $message,
                'approver_id' => $employee_id,
                'approver_photo' => $approverPhoto,
            ],
        ]);

        event(new NotificationEvent($notification));
    }

    private function createPendingNotification(Leave $leave, int $approver_id)
    {
        $approver = Employee::find($approver_id);
        $approverName = $approver ? $approver->full_name : 'Aprobador';
        $approverPhoto = $approver ? $approver->user->photo : null;
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
                'approver_photo' => $approverPhoto,
            ],
        ]);

        event(new NotificationEvent($notification));
    }




    private function handleRejectionOrCorrection(Leave $leave, string $action)
    {
        $leave->update(['state_id' => $this->getStateId($action)]);
    }

    private function getNextApprover(Leave $leave, int $employee_id)
    {
        // Lógica para determinar el siguiente aprobador basado en la jerarquía
        // Si el aprobador actual es el administrador, no se requiere otro aprobador
        $currentApprover = Employee::with('user.role')->find($employee_id);

        if (!$currentApprover || !$currentApprover->user || !$currentApprover->user->role) {
            throw new \Exception("No se pudo determinar el rol del aprobador actual");
        }

        $currentRole = $currentApprover->user->role->name;

        if ($currentRole === 'Administrador') {
            return null;
        }

        // Obtener el siguiente aprobador que tenga el rol de Administrador
        $nextApprover = Employee::whereHas('user', function ($query) {
            $query->whereHas('role', function ($q) {
                $q->where('name', 'Administrador');
            });
        })->first();

        if (!$nextApprover) {
            throw new \Exception("No se encontró el jefe de talento humano");
        }

        return $nextApprover;
    }

    private function getStateId(string $stateName)
    {
        return LeaveState::where('name', $stateName)->first()->id;
    }
}
