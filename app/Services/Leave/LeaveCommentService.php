<?php

namespace App\Services\Leave;

use App\Models\Leave\LeaveComment;
use App\Models\Leave\Leave;
use App\Models\Leave\LeaveState;
use App\Models\Employee\Employee;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
            }

            DB::commit();
            return $this->successResponse('Acción realizada con éxito', $comment);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al realizar la acción: ' . $e->getMessage(), 500);
        }
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
        } else {
            $leave->update(['state_id' => $this->getStateId('Aprobado')]);
        }
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
