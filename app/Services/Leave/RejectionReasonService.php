<?php

namespace App\Services\Leave;

use App\Models\Leave\RejectionReason;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RejectionReasonService extends ResponseService
{
    private function formatRejectionReasonData(RejectionReason $rejectionReason): array
    {
        $isActive = is_null($rejectionReason->deleted_at) ? 'Activo' : 'Inactivo';  // Determina si la razón está activa o inactiva
        return array_merge(
            $rejectionReason->only('id', 'reason'),
            ['status' => $isActive]  // Añade el estado 'activo' o 'inactivo'
        );
    }

    public function getAllRejectionReasons(bool $includeDeleted = false): JsonResponse
    {
        try {
            $query = RejectionReason::query();
            if ($includeDeleted) {
                $query->withTrashed(); // Incluye los registros eliminados lógicamente
            }
            $rejectionReasons = $query->get()->map(function ($rejectionReason) {
                return $this->formatRejectionReasonData($rejectionReason);
            });
            return $this->successResponse('Lista de motivos de rechazo obtenida con éxito', $rejectionReasons);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de motivos de rechazo: ' . $e->getMessage(), 500);
        }
    }

    public function createRejectionReason(array $data): JsonResponse
    {
        try {
            $rejectionReason = RejectionReason::create($data);
            return $this->successResponse('Motivo de rechazo creado con éxito', $this->formatRejectionReasonData($rejectionReason), 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear el motivo de rechazo: ' . $e->getMessage(), 500);
        }
    }

    public function getRejectionReasonById(string $id): JsonResponse
    {
        try {
            $rejectionReason = RejectionReason::withTrashed()->findOrFail($id);
            return $this->successResponse('Detalles del motivo de rechazo obtenidos con éxito', $this->formatRejectionReasonData($rejectionReason));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Motivo de rechazo no encontrado', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener el motivo de rechazo: ' . $e->getMessage(), 500);
        }
    }

    public function updateRejectionReason(string $id, array $data): JsonResponse
    {
        $rejectionReason = RejectionReason::findOrFail($id);

        try {
            $rejectionReason->update($data);
            return $this->successResponse('Motivo de rechazo actualizado con éxito', $this->formatRejectionReasonData($rejectionReason));
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

    public function toggleRejectionReasonStatus(string $id): JsonResponse
    {
        try {
            $rejectionReason = RejectionReason::withTrashed()->findOrFail($id);
            if ($rejectionReason->trashed()) {
                $rejectionReason->restore();
                $message = 'Motivo de rechazo activado con éxito';
            } else {
                $rejectionReason->delete();
                $message = 'Motivo de rechazo desactivado con éxito';
            }
            return $this->successResponse($message, $this->formatRejectionReasonData($rejectionReason), 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al cambiar el estado del motivo de rechazo: ' . $e->getMessage(), 500);
        }
    }
}