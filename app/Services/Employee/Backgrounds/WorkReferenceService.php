<?php

namespace App\Services\Employee\Backgrounds;

use App\Models\Employee\Backgrounds\WorkReference;
use Illuminate\Http\JsonResponse;
use App\Services\ResponseService;

class WorkReferenceService extends ResponseService
{
    public function getWorkReferences(int $employee_id): JsonResponse
    {
        try {
            $workReferences = WorkReference::where('employee_id', $employee_id)->get();
            return $this->successResponse('Lista de referencias laborales obtenida con éxito', $workReferences);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de referencias laborales', 500);
        }
    }

    public function createWorkReference(int $employee_id, array $data): JsonResponse
    {
        try {
            $workReferenceData = array_merge($data, ['employee_id' => $employee_id]);
            $workReference = WorkReference::create($workReferenceData);
            return $this->successResponse('Referencia laboral creada con éxito', $workReference, 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear la referencia laboral. Error: ' . $e->getMessage(), 500);
        }
    }
    
    public function getWorkReferenceById(int $employee_id, string $id): JsonResponse
    {
        try {
            $workReference = WorkReference::where('employee_id', $employee_id)->findOrFail($id);
            return $this->successResponse('Detalles de la referencia laboral obtenidos con éxito', $workReference);
        } catch (\Exception $e) {
            return $this->errorResponse('Referencia laboral no encontrada', 404);
        }
    }

    public function updateWorkReference(int $employee_id, string $id, array $data)
    {
        $workReference = WorkReference::where('employee_id', $employee_id)->findOrFail($id);
        try {
            $workReference->update($data);
            return ['message' => 'Referencia laboral actualizada con éxito', 'data' => $workReference];
        } catch (\Exception $e) {
            return ['message' => 'No se pudo actualizar la referencia laboral'];
        }
    }
    

    public function deleteWorkReference(int $employee_id, string $id): JsonResponse
    {
        try {
            $workReference = WorkReference::where('employee_id', $employee_id)->findOrFail($id);
            $workReference->delete();
            return $this->successResponse('Referencia laboral eliminada con éxito');
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo eliminar la referencia laboral', 500);
        }
    }
}
