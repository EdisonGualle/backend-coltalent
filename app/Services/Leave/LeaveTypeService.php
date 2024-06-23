<?php

namespace App\Services\Leave;

use App\Models\Leave\LeaveType;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class LeaveTypeService extends ResponseService
{
    public function getAllLeaveTypes(): JsonResponse
    {
        try {
            $leaveTypes = LeaveType::all();

            return $this->successResponse('Lista de tipos de permisos obtenida con éxito', $leaveTypes);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de tipos de permisos: ' . $e->getMessage(), 500);
        }
    }

    public function createLeaveType(array $data): JsonResponse
    {
        try {
            $leaveType = LeaveType::create($data);
            return $this->successResponse('Tipo de permiso creado con éxito', $leaveType, 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear el tipo de permiso: ' . $e->getMessage(), 500);
        }
    }

    public function getLeaveTypeById(string $id): JsonResponse
    {
        try {
            $leaveType = LeaveType::findOrFail($id);
            return $this->successResponse('Detalles del tipo de permiso obtenidos con éxito', $leaveType);
        } catch (\Exception $e) {
            return $this->errorResponse('Tipo de permiso no encontrado', 404);
        }
    }

    public function updateLeaveType(string $id, array $data): JsonResponse
    {
        $leaveType = LeaveType::findOrFail($id);

        try {
            $leaveType->update($data);
            return $this->successResponse('Tipo de permiso actualizado con éxito', $leaveType);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo actualizar el tipo de permiso: ' . $e->getMessage(), 500);
        }
    }

    public function deleteLeaveType(string $id): JsonResponse
    {
        $leaveType = LeaveType::findOrFail($id);

        try {
            $leaveType->delete();
            return $this->successResponse('Tipo de permiso eliminado con éxito');
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo eliminar el tipo de permiso', 500);
        }
    }
}
    