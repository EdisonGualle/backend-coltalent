<?php

namespace App\Services\Leave;

use App\Models\Leave\RejectionReason;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class RejectionReasonService extends ResponseService
{
    public function getAllRejectionReasons(): JsonResponse
    {
        try {
            $rejectionReasons = RejectionReason::all();

            return $this->successResponse('Lista de motivos de rechazo obtenida con éxito', $rejectionReasons);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de motivos de rechazo: ' . $e->getMessage(), 500);
        }
    }

    public function createRejectionReason(array $data): JsonResponse
    {
        try {
            $rejectionReason = RejectionReason::create($data);
            return $this->successResponse('Motivo de rechazo creado con éxito', $rejectionReason, 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear el motivo de rechazo: ' . $e->getMessage(), 500);
        }
    }

    public function getRejectionReasonById(string $id): JsonResponse
    {
        try {
            $rejectionReason = RejectionReason::findOrFail($id);
            return $this->successResponse('Detalles del motivo de rechazo obtenidos con éxito', $rejectionReason);
        } catch (\Exception $e) {
            return $this->errorResponse('Motivo de rechazo no encontrado', 404);
        }
    }

    public function updateRejectionReason(string $id, array $data): JsonResponse
    {
        $rejectionReason = RejectionReason::findOrFail($id);

        try {
            $rejectionReason->update($data);
            return $this->successResponse('Motivo de rechazo actualizado con éxito', $rejectionReason);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo actualizar el motivo de rechazo: ' . $e->getMessage(), 500);
        }
    }

    public function deleteRejectionReason(string $id): JsonResponse
    {
        $rejectionReason = RejectionReason::findOrFail($id);

        try {
            $rejectionReason->delete();
            return $this->successResponse('Motivo de rechazo eliminado con éxito');
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo eliminar el motivo de rechazo', 500);
        }
    }
}
