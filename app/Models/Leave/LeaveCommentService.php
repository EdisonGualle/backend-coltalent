<?php

namespace App\Services\Leave;

use App\Models\Leave\LeaveComment;
use App\Models\Leave\Leave;
use App\Models\Leave\LeaveState;
use App\Models\Employee\Employee;
use App\Models\Organization\Position;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LeaveCommentService extends ResponseService
{
    public function createComment(array $data): JsonResponse
    {
        DB::beginTransaction();

        try {
            // Crear el comentario
            $comment = LeaveComment::create($data);

            // Obtener el permiso asociado al comentario
            $leave = Leave::findOrFail($data['leave_id']);

            // Actualizar el estado del permiso según el comentario
            $leave->state_id = LeaveState::where('name', $data['action'])->first()->id;
            $leave->save();

            // Obtener el usuario que comentó
            $employee = Employee::findOrFail($data['commented_by']);
            $user = $employee->user;

            // Verificar si el usuario tiene el rol de administrador (jefe de talento humano)
            if ($user->role->name == 'Administrador') {
                // Aquí va la lógica para manejar la aprobación final del jefe de talento humano
            } else {
                // Aquí va la lógica para manejar los comentarios de otros roles
            }

            DB::commit();

            return $this->successResponse('Comentario creado con éxito', $comment, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('No se pudo crear el comentario: ' . $e->getMessage(), 500);
        }
    }
}
