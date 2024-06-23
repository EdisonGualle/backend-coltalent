<?php

namespace App\Services\Leave;

use App\Models\Leave\LeaveState;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class LeaveStateService extends ResponseService
{
    public function getAllLeaveStates(): JsonResponse
    {
        try {
            $leaveStates = LeaveState::all();

            return $this->successResponse('Lista de estados de permisos obtenida con éxito', $leaveStates);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de estados de permisos: ' . $e->getMessage(), 500);
        }
    }

    public function getLeaveStateById(string $id): JsonResponse
    {
        try {
            $leaveState = LeaveState::findOrFail($id);

            return $this->successResponse('Detalles del estado de permiso obtenidos con éxito', $leaveState);
        } catch (\Exception $e) {
            return $this->errorResponse('Estado de permiso no encontrado', 404);
        }
    }
}
