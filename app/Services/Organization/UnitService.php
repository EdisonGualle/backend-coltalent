<?php
namespace App\Services\Organization;

use App\Models\Organization\Unit;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class UnitService extends ResponseService
{
    private function formatUnitData(Unit $unit): array
    {
        $isActive = is_null($unit->deleted_at) ? 'Activo' : 'Inactivo'; 
        return array_merge(
            $unit->only('id', 'name', 'function', 'phone'),
            [
                'status' => $isActive,
                'direction' => $unit->direction ? $unit->direction->toArray() : null,
                'manager' => $unit->manager && $unit->manager->employee ? [
                    'id' => $unit->manager->employee->id,
                    'name' => $unit->manager->employee->name,
                    'photo' => $unit->manager->employee->userPhoto()
                ] : null,
            ]
        );
    }

    public function getAllUnits(bool $includeDeleted = false): JsonResponse
    {
        try {
            $query = Unit::with('direction', 'manager.employee');
            if ($includeDeleted) {
                $query->withTrashed();
            }
            $units = $query->get()->map(function ($unit) {
                return $this->formatUnitData($unit);
            });
            return $this->successResponse('Lista de unidades obtenida con éxito', $units, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de unidades: ' . $e->getMessage(), 500);
        }
    }
    public function createUnit(array $data): JsonResponse
    {
        try {
            $unit = Unit::create($data);
            $unit->load('direction');

            return $this->successResponse('Unidad creada con éxito', $this->formatUnitData($unit), 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear la unidad: ' . $e->getMessage(), 500);
        }
    }

    public function getUnitById(string $id): JsonResponse
    {
        try {
            $unit = Unit::with('direction', 'manager.employee')->findOrFail($id);

            return $this->successResponse('Detalles de la unidad obtenidos con éxito', $this->formatUnitData($unit));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Unidad no encontrada', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la unidad: ' . $e->getMessage(), 500);
        }
    }

    public function updateUnit(string $id, array $data): JsonResponse
    {
        $unit = Unit::findOrFail($id);

        try {
            $unit->update($data);
            $unit->load('direction');

            return $this->successResponse('Unidad actualizada con éxito', $this->formatUnitData($unit), 200);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo actualizar la unidad: ' . $e->getMessage(), 500);
        }
    }

    public function deleteUnit(string $id): JsonResponse
    {
        $unit = Unit::findOrFail($id);

        try {
            $unit->delete();
            return $this->successResponse('Unidad eliminada con éxito', null, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo eliminar la unidad', 500);
        }
    }


    public function toggleUnitStatus(string $id): JsonResponse
    {
        try {
            $unit = Unit::withTrashed()->findOrFail($id);
            if ($unit->trashed()) {
                $unit->restore();
                $message = 'Unidad activada con éxito';
            } else {
                $unit->delete();
                $message = 'Unidad desactivada con éxito';
            }
            return $this->successResponse($message, $this->formatUnitData($unit), 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al cambiar el estado de la unidad: ' . $e->getMessage(), 500);
        }
    }

}
