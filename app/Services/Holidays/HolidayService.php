<?php

namespace App\Services\Holidays;

use App\Models\Holidays\Holiday;
use Illuminate\Http\JsonResponse;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class HolidayService extends ResponseService
{
    public function getAllHolidays(bool $includeDeleted = false): JsonResponse
    {
        try {
            $query = Holiday::query();

            if ($includeDeleted) {
                $query->withTrashed();
            }

            $holidays = $query->get();

            $formattedHolidays = $holidays->map(fn(Holiday $holiday) => $this->formatHoliday($holiday));

            return $this->successResponse('Lista de días festivos obtenida con éxito', $formattedHolidays);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de días festivos: ' . $e->getMessage(), 500);
        }
    }


    public function createHoliday(array $data): JsonResponse
    {
        try {
            DB::beginTransaction();

            $holiday = Holiday::create($data);

            DB::commit();

            return $this->successResponse('Día festivo creado con éxito', $this->formatHoliday($holiday), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al crear el día festivo: ' . $e->getMessage(), 500);
        }
    }

    public function getHolidayById(int $id): JsonResponse
    {
        try {
            $holiday = Holiday::findOrFail($id);

            return $this->successResponse('Detalles del día festivo obtenidos con éxito', $this->formatHoliday($holiday));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Día festivo no encontrado.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener el día festivo: ' . $e->getMessage(), 500);
        }
    }

    public function updateHoliday(int $id, array $data): JsonResponse
    {
        try {
            DB::beginTransaction();

            $holiday = Holiday::findOrFail($id);
            $holiday->update($data);

            DB::commit();

            return $this->successResponse('Día festivo actualizado con éxito', $this->formatHoliday($holiday));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->errorResponse('Día festivo no encontrado.', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al actualizar el día festivo: ' . $e->getMessage(), 500);
        }
    }

    public function deleteHoliday(int $id): JsonResponse
    {
        try {
            $holiday = Holiday::findOrFail($id);
            $holiday->delete();

            return $this->successResponse('Día festivo eliminado con éxito');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Día festivo no encontrado.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar el día festivo: ' . $e->getMessage(), 500);
        }
    }

    public function restoreHoliday(int $id): JsonResponse
    {
        try {
            $holiday = Holiday::withTrashed()->findOrFail($id);
            $holiday->restore();

            return $this->successResponse('Día festivo restaurado con éxito', $this->formatHoliday($holiday));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Día festivo no encontrado.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al restaurar el día festivo: ' . $e->getMessage(), 500);
        }
    }

    public function getAssignableHolidays(): JsonResponse
    {
        try {
            // Filtrar solo días festivos que no aplican a todos y están activos
            $holidays = Holiday::where('applies_to_all', false)
                ->whereNull('deleted_at') // Solo los activos
                ->get()
                ->map(fn(Holiday $holiday) => $this->formatHoliday($holiday));

            return $this->successResponse('Lista de días festivos asignables obtenida con éxito', $holidays);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener los días festivos asignables: ' . $e->getMessage(), 500);
        }
    }


    private function formatHoliday(Holiday $holiday): array
    {
        return [
            'id' => $holiday->id,
            'name' => $holiday->name,
            'date' => $holiday->date,
            'is_recurring' => $holiday->is_recurring ? 'Recurrente' : 'No recurrente',
            'applies_to_all' => $holiday->applies_to_all ? 'Aplica a todos' : 'Aplica a algunos',
            'status' => $holiday->deleted_at ? 'Inactivo' : 'Activo',
        ];
    }
}
