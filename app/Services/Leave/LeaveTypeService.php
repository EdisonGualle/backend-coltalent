<?php

namespace App\Services\Leave;

use App\Models\Leave\LeaveType;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LeaveTypeService extends ResponseService
{
    private function formatLeaveTypeData(LeaveType $leaveType): array
    {
        $isActive = is_null($leaveType->deleted_at) ? 'Activo' : 'Inactivo';
        return array_merge(
            $leaveType->only('id', 'name', 'description', 'max_duration', 'requires_document', 'advance_notice_days', 'time_unit', 'icon'),
            ['status' => $isActive]
        );
    }

    public function getAllLeaveTypes(bool $includeDeleted = false): JsonResponse
    {
        try {
            $query = LeaveType::query();
            if ($includeDeleted) {
                $query->withTrashed();
            }
            $leaveTypes = $query->get()->map(function ($leaveType) {
                return $this->formatLeaveTypeData($leaveType);
            });

            return $this->successResponse('Lista de tipos de permisos obtenida con éxito', $leaveTypes);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de tipos de permisos: ' . $e->getMessage(), 500);
        }
    }

    public function createLeaveType(array $data): JsonResponse
    {
        try {
            $leaveType = LeaveType::create($data);
            return $this->successResponse('Tipo de permiso creado con éxito', $this->formatLeaveTypeData($leaveType), 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear el tipo de permiso: ' . $e->getMessage(), 500);
        }
    }

    public function getLeaveTypeById(string $id): JsonResponse
    {
        try {
            $leaveType = LeaveType::withTrashed()->findOrFail($id);
            return $this->successResponse('Detalles del tipo de permiso obtenidos con éxito', $this->formatLeaveTypeData($leaveType));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Tipo de permiso no encontrado', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener el tipo de permiso: ' . $e->getMessage(), 500);
        }
    }

    public function updateLeaveType(string $id, array $data): JsonResponse
    {
        $leaveType = LeaveType::findOrFail($id);

        try {
            $leaveType->update($data);
            return $this->successResponse('Tipo de permiso actualizado con éxito', $this->formatLeaveTypeData($leaveType));
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

    public function toggleLeaveTypeStatus(string $id): JsonResponse
    {
        try {
            $leaveType = LeaveType::withTrashed()->findOrFail($id);
            if ($leaveType->trashed()) {
                $leaveType->restore();
                $message = 'Tipo de permiso activado con éxito';
            } else {
                $leaveType->delete();
                $message = 'Tipo de permiso desactivado con éxito';
            }
            return $this->successResponse($message, $this->formatLeaveTypeData($leaveType), 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al cambiar el estado del tipo de permiso: ' . $e->getMessage(), 500);
        }
    }
}