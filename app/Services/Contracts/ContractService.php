<?php

namespace App\Services\Contracts;

use App\Models\Contracts\Contract;
use App\Models\Contracts\ContractType;
use App\Models\Employee\Employee;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class ContractService extends ResponseService
{
    public function getAllContracts(bool $includeDeleted = false): JsonResponse
    {
        try {
            $query = Contract::with([
                'employee.position.unit.direction',
                'contractType'
            ]);

            if ($includeDeleted) {
                $query->withTrashed();
            }

            $contracts = $query->orderBy('is_active', 'desc')
                ->orderBy('id', 'desc')
                ->get()
                ->map(fn(Contract $contract) => $this->formatContract($contract));

            return $this->successResponse('Lista de contratos obtenida con éxito', $contracts);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de contratos: ' . $e->getMessage(), 500);
        }
    }

    public function createContract(array $data): JsonResponse
    {
        try {
            DB::beginTransaction();

            $employee = Employee::findOrFail($data['employee_id']);
            $contractType = ContractType::findOrFail($data['contract_type_id']);

            $endDate = $contractType->max_duration_months
                ? Carbon::parse($data['start_date'])->addMonths($contractType->max_duration_months)
                : null;

                $vacationBalance = $contractType->vacation_days_per_year ?? null;

            $contract = $employee->contracts()->create([
                'contract_type_id' => $data['contract_type_id'],
                'start_date' => $data['start_date'],
                'end_date' => $endDate,
                'vacation_balance' => $vacationBalance, 
                'is_active' => true,
            ]);

            DB::commit();

            return $this->successResponse('Contrato creado con éxito', $this->formatContract($contract), 201);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->errorResponse('Empleado o tipo de contrato no encontrado', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al crear el contrato: ' . $e->getMessage(), 500);
        }
    }

    public function getContractById(string $id, bool $includeDeleted = false): JsonResponse
    {
        try {
            $query = Contract::with([
                'employee.position.unit.direction',
                'contractType'
            ]);

            if ($includeDeleted) {
                $query->withTrashed();
            }

            $contract = $query->findOrFail($id);

            return $this->successResponse('Detalles del contrato obtenidos con éxito', $this->formatContract($contract));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Contrato no encontrado', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener el contrato: ' . $e->getMessage(), 500);
        }
    }

    public function renewContract(string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Buscar el contrato con su tipo
            $currentContract = Contract::with('contractType')->findOrFail($id);

            // Validación 1: Verificar si el tipo de contrato permite renovaciones
            if (!$currentContract->contractType || !$currentContract->contractType->renewable) {
                return $this->errorResponse('Este tipo de contrato no permite renovaciones.', 400);
            }

            // Validación 2: Verificar si el contrato tiene una fecha de finalización
            if (is_null($currentContract->end_date)) {
                return $this->errorResponse('No se puede renovar un contrato que no tiene fecha de finalización.', 400);
            }

            // Validación 3: Verificar el rango de días permitido para la renovación
            $renewalDaysLimit = DB::table('configurations')
                ->where('key', 'contract_renewal_days_limit')
                ->value('value');
            $daysUntilEnd = now()->diffInDays(Carbon::parse($currentContract->end_date), false);

            if ($daysUntilEnd > $renewalDaysLimit) {
                return $this->errorResponse(
                    "El contrato aún no está próximo a expirar. Solo se puede renovar dentro de los {$renewalDaysLimit} días previos a la fecha de finalización.",
                    400
                );
            }

            // Calcular fechas para el nuevo contrato
            $startDate = Carbon::parse($currentContract->end_date)->addDay();
            $endDate = $currentContract->contractType->max_duration_months
                ? $startDate->copy()->addMonths($currentContract->contractType->max_duration_months)
                : null;

            // Crear el nuevo contrato renovado
            $newContract = $currentContract->employee->contracts()->create([
                'contract_type_id' => $currentContract->contract_type_id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => true,
            ]);

            DB::commit();

            // Respuesta con ambos contratos
            return $this->successResponse('Contrato renovado con éxito.', [
                'current_contract' => $this->formatContract($currentContract),
                'new_contract' => $this->formatContract($newContract),
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->errorResponse('Contrato no encontrado.', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al renovar el contrato: ' . $e->getMessage(), 500);
        }
    }



    public function terminateContract(string $id, string $reason): JsonResponse
    {
        try {
            $contract = Contract::findOrFail($id);

            // Verificar si el contrato es renovado y el empleado tiene más de un contrato activo
            $activeContracts = Contract::where('employee_id', $contract->employee_id)
                ->where('is_active', true)
                ->orderBy('start_date', 'asc') // Ordenar por la fecha de inicio
                ->get();

            // Impedir la terminación si hay más de un contrato activo y este es renovado
            if ($activeContracts->count() > 1 && $activeContracts->first()->id === $contract->id) {
                return $this->errorResponse(
                    'No se puede terminar este contrato porque está relacionado con una renovación activa.',
                    400
                );
            }

            // Obtener el límite mínimo de días desde la configuración
            $minDaysToTerminate = DB::table('configurations')
                ->where('key', 'contract_min_days_to_terminate')
                ->value('value');

            // Validar antigüedad del contrato
            $daysSinceStart = Carbon::parse($contract->start_date)->diffInDays(now());
            if ($daysSinceStart < $minDaysToTerminate) {
                return $this->errorResponse(
                    "El contrato no puede finalizarse antes de {$minDaysToTerminate} días desde su inicio.",
                    400
                );
            }

            // Finalizar contrato
            $contract->update([
                'end_date' => now(),
                'is_active' => false,
                'termination_reason' => $reason,
            ]);

            return $this->successResponse('Contrato finalizado con éxito', $this->formatContract($contract));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Contrato no encontrado.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al finalizar el contrato: ' . $e->getMessage(), 500);
        }
    }


    private function formatContract(Contract $contract): array
    {
        return [
            'id' => $contract->id,
            'employee' => $contract->employee ? [
                'id' => $contract->employee->id,
                'identification' => $contract->employee->identification,
                'full_name' => $contract->employee->full_name,
                'organization' => [
                    'position' => $contract->employee->position?->name ?? 'No especificado',
                    'unit' => $contract->employee->position?->unit?->name ?? 'No especificado',
                    'direction' => $contract->employee->position?->unit?->direction?->name ?? 'No especificado',
                ],
            ] : null,
            'contract_type' => $contract->contractType ? $contract->contractType->only(['id', 'name']) : null,
            'start_date' => $contract->start_date ? Carbon::parse($contract->start_date)->format('Y-m-d') : null,
            'end_date' => $contract->end_date ? Carbon::parse($contract->end_date)->format('Y-m-d') : null,
            'termination_reason' => $contract->termination_reason,
            'vacation_balance' => $contract->vacation_balance,
            'status' => $contract->is_active ? 'Activo' : 'Finalizado',
        ];
    }
}
