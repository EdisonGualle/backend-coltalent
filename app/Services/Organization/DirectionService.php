<?php

namespace App\Services\Organization;

use App\Models\Organization\Direction;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class DirectionService extends ResponseService
{
    private function formatDirectionData(Direction $direction): array
    {
        $isActive = is_null($direction->deleted_at) ? 'Activo' : 'Inactivo';
        return array_merge(
            $direction->only('id', 'name', 'function'),
            [ 
                'status' => $isActive, 
                'manager' => $direction->manager && $direction->manager->employee ? [
                    'id' => $direction->manager->employee->id,
                    'name' => $direction->manager->employee->name,
                    'photo' => $direction->manager->employee->userPhoto()
                ] : null,
            ]
        );
    }

    public function getAllDirections(bool $includeDeleted = false): JsonResponse
    {
        try {
            // Inicia la consulta preparada para incluir relaciones relevantes
            $query = Direction::with(['manager.employee']);

            // Condiciona la inclusión de registros eliminados lógicamente
            if ($includeDeleted) {
                $query->withTrashed(); // Incluye los registros eliminados lógicamente
            }

            // Ejecuta la consulta y formatea cada dirección
            $directions = $query->get()->map(function ($direction) {
                return $this->formatDirectionData($direction);
            });

            // Devuelve una respuesta exitosa con las direcciones obtenidas
            return $this->successResponse('Lista de direcciones obtenida con éxito', $directions, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de direcciones: ' . $e->getMessage(), 500);
        }
    }

    public function createDirection(array $data): JsonResponse
    {
        try {
            $direction = Direction::create($data);

            return $this->successResponse('Dirección creada con éxito', $this->formatDirectionData($direction), 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear la dirección: ' . $e->getMessage(), 500);
        }
    }

    public function getDirectionById(string $id): JsonResponse
    {
        try {
            $direction = Direction::with('manager.employee')->findOrFail($id);

            return $this->successResponse('Detalles de la dirección obtenidos con éxito', $this->formatDirectionData($direction));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Dirección no encontrada', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la dirección: ' . $e->getMessage(), 500);
        }
    }

    public function updateDirection(string $id, array $data): JsonResponse
    {
        $direction = Direction::findOrFail($id);

        try {
            $direction->update($data);

            return $this->successResponse('Dirección actualizada con éxito', $this->formatDirectionData($direction), 200);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo actualizar la dirección: ' . $e->getMessage(), 500);
        }
    }

    public function deleteDirection(string $id): JsonResponse
    {
        $direction = Direction::findOrFail($id);

        try {
            $direction->delete();
            return $this->successResponse('Dirección eliminada con éxito', null, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo eliminar la dirección: ' . $e->getMessage(), 500);
        }
    }

    public function toggleDirectionStatus(string $id): JsonResponse
    {
        try {
            $direction = Direction::withTrashed()->findOrFail($id);  // Busca incluyendo los eliminados lógicamente

            if ($direction->trashed()) {
                $direction->restore();  // Restaura si está eliminada
                $message = 'Dirección activada con éxito';
            } else {
                $direction->delete();  // Elimina lógicamente si está activa
                $message = 'Dirección desactivada con éxito';
            }

            // Devuelve una respuesta JSON indicando el éxito de la operación
            return $this->successResponse($message, $this->formatDirectionData($direction), 200);
        } catch (\Exception $e) {
            // Maneja cualquier excepción y devuelve un error
            return $this->errorResponse('Error al cambiar el estado de la dirección: ' . $e->getMessage(), 500);
        }
    }

}
