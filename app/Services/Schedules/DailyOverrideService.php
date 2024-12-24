<?php

namespace App\Services\Schedules;

use App\Models\Schedules\DailyOverride;
use App\Models\Employee\Employee;
use App\Models\Contracts\Contract;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DailyOverrideService extends ResponseService
{
    /**
     * Crear un nuevo ajuste temporal de horario.
     */
    public function createDailyOverride(array $data): JsonResponse
    {
        try {
            $employee = Employee::findOrFail($data['employee_id']);
            $contract = $employee->contracts()->where('is_active', true)->first();

            if (!$contract) {
                return $this->errorResponse('El empleado no tiene un contrato activo.', 400);
            }

            // Validar rango de fechas del contrato
            if ($data['date'] < now()->toDateString()) {
                return $this->errorResponse('La fecha del ajuste debe ser futura.', 422);
            }

            if ($data['date'] < $contract->start_date || ($contract->end_date && $data['date'] > $contract->end_date)) {
                return $this->errorResponse(
                    'La fecha del ajuste debe estar dentro del rango del contrato activo.',
                    422
                );
            }

            // Crear el ajuste temporal
            $override = DailyOverride::create($data);

            return $this->successResponse('Ajuste temporal creado con éxito.', $override, 201);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Empleado no encontrado.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear el ajuste temporal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener todos los ajustes temporales (incluidos los eliminados).
     */
    public function getAllOverrides(bool $includeDeleted = false): JsonResponse
    {
        try {
            $query = DailyOverride::query();

            if ($includeDeleted) {
                $query->withTrashed();
            }

            $overrides = $query->get();

            return $this->successResponse('Lista de ajustes temporales obtenida con éxito.', $overrides);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener los ajustes temporales: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Actualizar un ajuste temporal.
     */
    public function updateDailyOverride(int $id, array $data): JsonResponse
    {
        try {
            $override = DailyOverride::findOrFail($id);

            if ($override->date < now()->toDateString()) {
                return $this->errorResponse('No se puede actualizar un ajuste temporal cuya fecha ya pasó.', 422);
            }

            $override->update($data);

            return $this->successResponse('Ajuste temporal actualizado con éxito.', $override);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Ajuste temporal no encontrado.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar el ajuste temporal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Eliminar un ajuste temporal (lógica).
     */
    public function deleteDailyOverride(int $id): JsonResponse
    {
        try {
            $override = DailyOverride::findOrFail($id);

            if ($override->date < now()->toDateString()) {
                return $this->errorResponse('No se puede eliminar un ajuste temporal cuya fecha ya pasó.', 422);
            }

            $override->delete();

            return $this->successResponse('Ajuste temporal eliminado con éxito.');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Ajuste temporal no encontrado.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar el ajuste temporal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Restaurar un ajuste temporal eliminado.
     */
    public function restoreDailyOverride(int $id): JsonResponse
    {
        try {
            $override = DailyOverride::withTrashed()->findOrFail($id);

            if ($override->date < now()->toDateString()) {
                return $this->errorResponse('No se puede restaurar un ajuste temporal cuya fecha ya pasó.', 422);
            }

            $override->restore();

            return $this->successResponse('Ajuste temporal restaurado con éxito.', $override);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Ajuste temporal no encontrado.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al restaurar el ajuste temporal: ' . $e->getMessage(), 500);
        }
    }

    public function getOverridesByEmployee(int $employeeId, bool $includeDeleted = false): JsonResponse
{
    try {
        $query = DailyOverride::where('employee_id', $employeeId);

        if ($includeDeleted) {
            $query->withTrashed();
        }

        $overrides = $query->get();

        if ($overrides->isEmpty()) {
            return $this->errorResponse('No se encontraron ajustes temporales para este empleado.', 404);
        }

        return $this->successResponse('Ajustes temporales del empleado obtenidos con éxito.', $overrides);
    } catch (\Exception $e) {
        return $this->errorResponse('Error al obtener los ajustes temporales: ' . $e->getMessage(), 500);
    }
}

}
