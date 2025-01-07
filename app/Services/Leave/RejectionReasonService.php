<?php

namespace App\Services\Leave;

use App\Models\Leave\RejectionReason;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RejectionReasonService extends ResponseService
{
    private function formatRejectionReasonData(RejectionReason $rejectionReason): array
    {
        $isActive = is_null($rejectionReason->deleted_at) ? 'Activo' : 'Inactivo';

        // Carga los leaveTypes si no están cargados (seguridad adicional)
        if (!$rejectionReason->relationLoaded('leaveTypes')) {
            $rejectionReason->load('leaveTypes');
        }

        return [
            'id' => $rejectionReason->id,
            'reason' => $rejectionReason->reason,
            'status' => $isActive,
            'leave_types' => $rejectionReason->leaveTypes->map(function ($leaveType) {
                return [
                    'id' => $leaveType->id,
                    'name' => $leaveType->name,
                ];
            })->toArray(), // Convertir la colección a un array para asegurarnos de la estructura
        ];
    }

    public function getAllRejectionReasons(bool $includeDeleted = false): JsonResponse
    {
        try {
            $query = RejectionReason::with('leaveTypes');
            if ($includeDeleted) {
                $query = $query->withTrashed();
            }

            $rejectionReasons = $query->get()->map(function ($rejectionReason) {
                return $this->formatRejectionReasonData($rejectionReason);
            })->toArray();

            return $this->successResponse('Lista de motivos de rechazo obtenida con éxito', $rejectionReasons);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de motivos de rechazo: ' . $e->getMessage(), 500);
        }
    }

    public function createRejectionReason(array $data): JsonResponse
    {
        try {
            // Crear el motivo de rechazo
            $rejectionReason = RejectionReason::create(['reason' => $data['reason']]);

            // Asociar los tipos de permisos
            if (!empty($data['leave_type_ids'])) {
                $rejectionReason->leaveTypes()->sync($data['leave_type_ids']);
            }

            // Cargar explícitamente los leaveTypes desde la tabla intermedia
            $leaveTypes = DB::table('leave_type_rejection_reason')
                ->join('leave_types', 'leave_type_rejection_reason.leave_type_id', '=', 'leave_types.id')
                ->where('leave_type_rejection_reason.rejection_reason_id', $rejectionReason->id)
                ->select('leave_types.id', 'leave_types.name')
                ->get();


            // Asignar la relación manualmente
            $rejectionReason->setRelation('leaveTypes', $leaveTypes);

            // Generar la respuesta
            $formattedData = $this->formatRejectionReasonData($rejectionReason);

            return $this->successResponse(
                'Motivo de rechazo creado con éxito y asociado a tipos de permisos.',
                $formattedData,
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear el motivo de rechazo: ' . $e->getMessage(), 500);
        }
    }




    public function updateRejectionReason(string $id, array $data): JsonResponse
    {
        try {
            // Buscar el motivo de rechazo
            $rejectionReason = RejectionReason::findOrFail($id);

            // Actualizar el motivo si se proporcionó
            if (!empty($data['reason'])) {
                $rejectionReason->update(['reason' => $data['reason']]);
            }

            // Sincronizar los tipos de permisos
            if (isset($data['leave_type_ids'])) {
                $rejectionReason->leaveTypes()->sync($data['leave_type_ids']);
            }

            // Forzar recarga de relaciones con una consulta directa
            $leaveTypes = DB::table('leave_type_rejection_reason')
                ->join('leave_types', 'leave_type_rejection_reason.leave_type_id', '=', 'leave_types.id')
                ->where('leave_type_rejection_reason.rejection_reason_id', $rejectionReason->id)
                ->select('leave_types.id', 'leave_types.name')
                ->get();

            // Asignar la relación manualmente
            $rejectionReason->setRelation('leaveTypes', $leaveTypes);

            // Generar la respuesta
            $formattedData = $this->formatRejectionReasonData($rejectionReason);

            return $this->successResponse(
                'Motivo de rechazo actualizado con éxito.',
                $formattedData
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Motivo de rechazo no encontrado.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'No se pudo actualizar el motivo de rechazo: ' . $e->getMessage(),
                500
            );
        }
    }


    public function deleteRejectionReason(string $id): JsonResponse
    {
        try {
            $rejectionReason = RejectionReason::findOrFail($id);
            $rejectionReason->delete();

            return $this->successResponse('Motivo de rechazo eliminado con éxito.');
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo eliminar el motivo de rechazo.', 500);
        }
    }

    public function toggleRejectionReasonStatus(string $id): JsonResponse
    {
        try {
            $rejectionReason = RejectionReason::withTrashed()->findOrFail($id);
            if ($rejectionReason->trashed()) {
                $rejectionReason->restore();
                $message = 'Motivo de rechazo activado con éxito.';
            } else {
                $rejectionReason->delete();
                $message = 'Motivo de rechazo desactivado con éxito.';
            }

            return $this->successResponse(
                $message,
                $this->formatRejectionReasonData($rejectionReason)
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Error al cambiar el estado del motivo de rechazo: ' . $e->getMessage(), 500);
        }
    }
}
