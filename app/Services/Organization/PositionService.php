<?php

namespace App\Services\Organization;

use App\Models\Organization\Position;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class PositionService extends ResponseService
{
    private function formatPositionData(Position $position): array
    {
        $isActive = is_null($position->deleted_at) ? 'Activo' : 'Inactivo';
        return array_merge(
            $position->only('id', 'name', 'function', 'is_manager', 'is_general_manager'),
            [
                'status' => $isActive,
                'unit' => $position->unit ? [
                    'id' => $position->unit->id,
                    'name' => $position->unit->name,
                ] : null,
                'direction' => $position->direction ? [
                    'id' => $position->direction->id,
                    'name' => $position->direction->name,
                ] : null,
                'responsibilities' => $position->responsibilities->map(function ($responsibility) {
                    return [
                        'id' => $responsibility->id,
                        'name' => $responsibility->name,
                        'description' => $responsibility->description,
                    ];
                })->toArray(),
            ]
        );
    }

    public function getAllPositions(bool $includeDeleted = false): JsonResponse
    {
        try {
            $query = Position::with([
                'unit' => function ($query) use ($includeDeleted) {
                    if ($includeDeleted) {
                        $query->withTrashed();
                    }
                },
                'direction' => function ($query) use ($includeDeleted) {
                    if ($includeDeleted) {
                        $query->withTrashed();
                    }
                },
                'responsibilities'
            ]);

            if ($includeDeleted) {
                $query->withTrashed();
            }

            $positions = $query->get()->map(function ($position) {
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
            DB::beginTransaction();

            // Extraer responsabilidades del payload y excluirlo de los datos de Position
            $responsibilities = $data['responsibilities'] ?? [];
            unset($data['responsibilities']);

            $position = Position::create($data);

            // Crear responsabilidades asociadas si existen
            if (!empty($responsibilities)) {
                foreach ($responsibilities as $responsibility) {
                    $position->responsibilities()->create(['name' => $responsibility]);
                }
            }
            // Confirmar la transacción
            DB::commit();

            // Cargar relaciones para devolver la respuesta completa
            $position->load('unit', 'direction', 'responsibilities');

            return $this->successResponse('Posición creada con éxito', $this->formatPositionData($position), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('No se pudo crear la posición: ' . $e->getMessage(), 500);
        }
    }

    public function getPositionById(string $id, bool $includeDeleted = false): JsonResponse
    {
        try {
            $query = Position::with([
                'unit' => function ($query) use ($includeDeleted) {
                    if ($includeDeleted) {
                        $query->withTrashed();
                    }
                },
                'direction' => function ($query) use ($includeDeleted) {
                    if ($includeDeleted) {
                        $query->withTrashed();
                    }
                },
                 'responsibilities'
            ]);

            if ($includeDeleted) {
                $query->withTrashed();
            }

            $position = $query->findOrFail($id);

            return $this->successResponse('Detalles de la posición obtenidos con éxito', $this->formatPositionData($position));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Posición no encontrada', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la posición: ' . $e->getMessage(), 500);
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

    public function togglePositionStatus(string $id): JsonResponse
    {
        try {
            $position = Position::withTrashed()->findOrFail($id);
            if ($position->trashed()) {
                $position->restore();
                $message = 'Posición activada con éxito';
            } else {
                $position->delete();
                $message = 'Posición desactivada con éxito';
            }
            return $this->successResponse($message, $this->formatPositionData($position), 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al cambiar el estado de la posición: ' . $e->getMessage(), 500);
        }
    }


}
