<?php

namespace App\Services\Organization;

use App\Models\Organization\Position;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class PositionService extends ResponseService
{
    private function formatPositionData(Position $position): array
    {
        return array_merge(
            $position->only('id', 'name', 'function', 'is_manager', 'is_general_manager'),
            [
                'unit' => $position->unit ? [
                    'id' => $position->unit->id,
                    'name' => $position->unit->name,
                ] : null,
                'direction' => $position->direction ? [
                    'id' => $position->direction->id,
                    'name' => $position->direction->name,
                ] : null,
            ]
        );
    }

    public function getAllPositions(): JsonResponse
    {
        try {
            $positions = Position::with('unit:id,name', 'direction:id,name')
                ->get()
                ->map(function ($position) {
                    return $this->formatPositionData($position);
                });

            return $this->successResponse('Lista de posiciones obtenida con éxito', $positions);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de posiciones: ' . $e->getMessage(), 500);
        }
    }

    public function createPosition(array $data): JsonResponse
    {
        try {
            $position = Position::create($data);
            $position->load('unit', 'direction');

            return $this->successResponse('Posición creada con éxito', $this->formatPositionData($position), 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear la posición: ' . $e->getMessage(), 500);
        }
    }

    public function getPositionById(string $id): JsonResponse
    {
        try {
            $position = Position::with('unit:id,name', 'direction:id,name')->findOrFail($id);

            return $this->successResponse('Detalles de la posición obtenidos con éxito', $this->formatPositionData($position));
        } catch (\Exception $e) {
            return $this->errorResponse('Posición no encontrada', 404);
        }
    }

    public function updatePosition(string $id, array $data): JsonResponse
    {
        $position = Position::findOrFail($id);

        try {
            $position->update($data);
            $position->load('unit', 'direction');

            return $this->successResponse('Posición actualizada con éxito', $this->formatPositionData($position));
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo actualizar la posición: ' . $e->getMessage(), 500);
        }
    }

    public function deletePosition(string $id): JsonResponse
    {
        $position = Position::findOrFail($id);

        try {
            $position->delete();
            return $this->successResponse('Posición eliminada con éxito');
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo eliminar la posición', 500);
        }
    }
}
