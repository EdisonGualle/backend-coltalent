<?php

namespace App\Services\Holidays;

use App\Models\Holidays\HolidayWorkRecord;
use App\Models\Holidays\Holiday;
use App\Models\Employee\Employee;
use Illuminate\Http\JsonResponse;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HolidayWorkRecordService extends ResponseService
{
    /**
     * Crear registros de trabajo en rango de días festivos.
     */
    public function createWorkRecords(array $data): JsonResponse
    {
        try {
            DB::beginTransaction();
    
            $employee = Employee::find($data['employee_id']);
            if (!$employee) {
                return $this->errorResponse("El empleado no existe.", 400);
            }
    
            $holidayIds = $data['holiday_ids'];
            $invalidHolidays = [];
            $duplicatedRecords = [];
    
            foreach ($holidayIds as $holidayId) {
                $holiday = Holiday::find($holidayId);
                if (!$holiday) {
                    $invalidHolidays[] = $holidayId;
                    continue;
                }
    
                $exists = HolidayWorkRecord::where('holiday_id', $holidayId)
                    ->where('employee_id', $data['employee_id'])
                    ->whereNull('deleted_at') // Solo registros activos
                    ->exists();
    
                if ($exists) {
                    $duplicatedRecords[] = $holidayId;
                    continue;
                }
    
                if (!$holiday->applies_to_all) {
                    $isAssigned = $holiday->assignments()
                        ->where('employee_id', $data['employee_id'])
                        ->exists();
    
                    if (!$isAssigned) {
                        $invalidHolidays[] = $holidayId;
                        continue;
                    }
                }
    
                HolidayWorkRecord::create([
                    'employee_id' => $data['employee_id'],
                    'holiday_id' => $holidayId,
                    'type' => $data['type'],
                    'worked_value' => $data['worked_value'],
                    'generates_compensatory' => $data['generates_compensatory'] ?? true,
                    'reason' => $data['reason'],
                ]);
            }
    
            DB::commit();
    
            if (!empty($invalidHolidays) || !empty($duplicatedRecords)) {
                $invalidList = implode(', ', $invalidHolidays);
                $duplicatedList = implode(', ', $duplicatedRecords);
                return $this->errorResponse("Errores: Festivos inválidos: [{$invalidList}]. Duplicados: [{$duplicatedList}]", 400);
            }
    
            return $this->successResponse('Registros de trabajo creados correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al crear los registros de trabajo: ' . $e->getMessage(), 400);
        }
    }
    
    /**
     * Obtener todos los registros activos.
     */
    public function getAllActiveWorkRecords(): JsonResponse
    {
        try {
            $records = HolidayWorkRecord::with(['holiday', 'employee'])
                ->whereNull('deleted_at') // Solo registros activos
                ->get();

            $formattedRecords = $records->map(fn($record) => $this->formatWorkRecord($record));

            return $this->successResponse('Registros de trabajo activos obtenidos con éxito.', $formattedRecords);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener los registros de trabajo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener registros activos para un empleado específico.
     */
    public function getActiveWorkRecordsByEmployee(int $employeeId): JsonResponse
    {
        try {
            $records = HolidayWorkRecord::with(['holiday', 'employee'])
                ->where('employee_id', $employeeId)
                ->whereNull('deleted_at') // Solo registros activos
                ->get();

            $formattedRecords = $records->map(fn($record) => $this->formatWorkRecord($record));

            return $this->successResponse("Registros de trabajo activos para el empleado {$employeeId} obtenidos con éxito.", $formattedRecords);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener los registros de trabajo del empleado: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Obtener un registro específico.
     */
    public function getWorkRecordById(int $recordId): JsonResponse
    {
        try {
            $record = HolidayWorkRecord::with(['holiday', 'employee'])
                ->find($recordId);

            if (!$record) {
                return $this->errorResponse("El registro de trabajo no existe.", 404);
            }

            return $this->successResponse('Registro de trabajo obtenido con éxito.', $this->formatWorkRecord($record));
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener el registro de trabajo: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Eliminar registros de trabajo en rango.
     */
    public function deleteWorkRecords(array $recordIds): JsonResponse
    {
        try {
            DB::beginTransaction();

            $records = HolidayWorkRecord::whereIn('id', $recordIds)
                ->whereNull('deleted_at') // Solo registros activos
                ->get();

            if ($records->isEmpty()) {
                throw new \Exception('No se encontraron registros activos para eliminar.');
            }

            foreach ($records as $record) {
                $holidayDate = Carbon::parse($record->holiday->date);
                if ($holidayDate->isPast()) {
                    throw new \Exception("No se puede eliminar un registro de trabajo para el día festivo pasado: {$record->holiday->name}.");
                }

                $record->delete(); // Eliminación lógica
            }

            DB::commit();
            return $this->successResponse("Registros eliminados correctamente: {$records->count()} registros afectados.");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al eliminar los registros de trabajo: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Formatear un registro de trabajo para la respuesta.
     */
    private function formatWorkRecord(HolidayWorkRecord $workRecord): array
    {
        return [
            'id' => $workRecord->id,
            'employee' => $workRecord->employee->only(['id', 'first_name', 'last_name']),
            'holiday' => $workRecord->holiday->only(['id', 'name', 'date']),
            'type' => $workRecord->type,
            'worked_value' => $workRecord->worked_value,
            'reason' => $workRecord->reason,
            'generates_compensatory' => $workRecord->generates_compensatory,
            'created_at' => $workRecord->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
