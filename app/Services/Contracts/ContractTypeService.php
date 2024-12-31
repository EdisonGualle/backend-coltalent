<?php

namespace App\Services\Contracts;

use App\Models\Contracts\ContractType;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ContractTypeService extends ResponseService
{
    /**
     * Obtener todos los tipos de contratos.
     */
    public function getAllContractTypes(): JsonResponse
    {
        try {
            // Incluye todos los tipos de contrato, activos y eliminados lógicamente
            $contractTypes = ContractType::withTrashed()
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($contractType) {
                return $this->formatContractType($contractType);
            });

            return $this->successResponse('Lista de tipos de contrato obtenida con éxito', $contractTypes);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de tipos de contrato: ' . $e->getMessage(), 500);
        }
    }
    

    /**
     * Crear un nuevo tipo de contrato.
     */
    public function createContractType(array $data): JsonResponse
    {
        try {
            $contractType = ContractType::create($data);

            return $this->successResponse('Tipo de contrato creado con éxito', $this->formatContractType($contractType), 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear el tipo de contrato: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener detalles de un tipo de contrato por ID.
     */
    public function getContractTypeById(string $id): JsonResponse
    {
        try {
            $contractType = ContractType::findOrFail($id);

            return $this->successResponse('Detalles del tipo de contrato obtenidos con éxito', $this->formatContractType($contractType));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Tipo de contrato no encontrado', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener el tipo de contrato: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Actualizar un tipo de contrato.
     */
    public function updateContractType(string $id, array $data): JsonResponse
    {
        try {
            $contractType = ContractType::findOrFail($id);
            $contractType->update($data);

            return $this->successResponse('Tipo de contrato actualizado con éxito', $this->formatContractType($contractType));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Tipo de contrato no encontrado', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar el tipo de contrato: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Eliminar un tipo de contrato (eliminación lógica).
     */
    public function deleteContractType(string $id): JsonResponse
    {
        try {
            $contractType = ContractType::findOrFail($id);
            $contractType->delete();

            return $this->successResponse('Tipo de contrato eliminado con éxito',$this->formatContractType($contractType) );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Tipo de contrato no encontrado', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar el tipo de contrato: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Restaurar un tipo de contrato eliminado.
     */
    public function restoreContractType(string $id): JsonResponse
    {
        try {
            $contractType = ContractType::withTrashed()->findOrFail($id);
            $contractType->restore();

            return $this->successResponse('Tipo de contrato restaurado con éxito', $this->formatContractType($contractType));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Tipo de contrato no encontrado', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al restaurar el tipo de contrato: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Formatear datos del tipo de contrato.
     */
    private function formatContractType(ContractType $contractType): array
    {
        return [
            'id' => $contractType->id,
            'name' => $contractType->name,
            'description' => $contractType->description,
            'max_duration_months' => $contractType->max_duration_months,
            'renewable' => $contractType->renewable,
            'vacation_days_per_year' => $contractType->vacation_days_per_year,
            'max_vacation_days' => $contractType->max_vacation_days,
            'min_tenure_months_for_vacation' => $contractType->min_tenure_months_for_vacation,
            'weekly_hours' => $contractType->weekly_hours,
            'status' => $contractType->deleted_at ? 'Inactivo' : 'Activo',
        ];
    }
}
