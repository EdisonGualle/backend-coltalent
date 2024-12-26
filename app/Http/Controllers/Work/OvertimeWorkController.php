<?php

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Controller;
use App\Http\Requests\Work\CreateOvertimeWorkRequest;
use App\Http\Requests\Work\DeleteOvertimeWorkRecordsRequest;
use App\Services\Work\OvertimeWorkService;
use Illuminate\Http\JsonResponse;

class OvertimeWorkController extends Controller
{
    protected $workRecordService;

    public function __construct(OvertimeWorkService $workRecordService)
    {
        $this->workRecordService = $workRecordService;
    }

    /**
     * Crear un registro de trabajo.
     */
    public function store(CreateOvertimeWorkRequest $request): JsonResponse
    {
        return $this->workRecordService->createWorkRecord($request->validated());
    }

    /**
     * Obtener todos los registros activos.
     */
    public function index(): JsonResponse
    {
        return $this->workRecordService->getAllActiveWorkRecords();
    }

    /**
     * Obtener registros activos de un empleado.
     */
    public function showByEmployee(int $employeeId): JsonResponse
    {
        return $this->workRecordService->getActiveWorkRecordsByEmployee($employeeId);
    }

    /**
     * Obtener un registro específico.
     */
    public function show(int $recordId): JsonResponse
    {
        return $this->workRecordService->getWorkRecordById($recordId);
    }

    /**
     * Eliminar registros en rango.
     */
    public function destroy(DeleteOvertimeWorkRecordsRequest $request): JsonResponse
    {
        return $this->workRecordService->deleteWorkRecords($request->validated()['record_ids']);
    }
}
